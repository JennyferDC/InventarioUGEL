package client

import (
	"bytes"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"net/http"
	"strings"
	"time"

	"ugel-inventario-agente/internal/persona"
	"ugel-inventario-agente/internal/processor"
)

const (
	DefaultServerURL  = "http://127.0.0.1:8000"
	DefaultAgentToken = "ugel_agente_hardware_secret_token_2026"
)

// ApiResponse represents the standard response envelope from the Laravel API
type ApiResponse struct {
	Success bool            `json:"success"`
	Action  string          `json:"action,omitempty"`
	Message string          `json:"message"`
	Data    json.RawMessage `json:"data,omitempty"`
	Errors  json.RawMessage `json:"errors,omitempty"`
}

// FetchRemoteOficinas attempts to load offices from the remote Laravel API
func FetchRemoteOficinas(serverURL string) ([]persona.Oficina, error) {
	serverURL = strings.TrimRight(serverURL, "/")
	url := fmt.Sprintf("%s/api/v1/agente/oficinas", serverURL)

	httpClient := &http.Client{
		Timeout: 5 * time.Second,
	}

	resp, err := httpClient.Get(url)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return nil, fmt.Errorf("código de estado inesperado al consultar oficinas: %d", resp.StatusCode)
	}

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return nil, err
	}

	var apiResp struct {
		Success bool              `json:"success"`
		Data    []persona.Oficina `json:"data"`
	}

	if err := json.Unmarshal(body, &apiResp); err != nil {
		return nil, err
	}

	if len(apiResp.Data) == 0 {
		return nil, errors.New("el servidor no devolvió oficinas")
	}

	return apiResp.Data, nil
}

// SendRegistration transmits the unified payload to the Laravel backend API
func SendRegistration(serverURL, token string, payload *processor.UnifiedPayload) (*ApiResponse, error) {
	serverURL = strings.TrimRight(serverURL, "/")
	url := fmt.Sprintf("%s/api/v1/agente/registro", serverURL)

	if token == "" {
		token = DefaultAgentToken
	}

	payloadBytes, err := processor.ToJSON(payload, false)
	if err != nil {
		return nil, fmt.Errorf("error serializando payload: %w", err)
	}

	req, err := http.NewRequest(http.MethodPost, url, bytes.NewBuffer(payloadBytes))
	if err != nil {
		return nil, fmt.Errorf("error preparando petición HTTP: %w", err)
	}

	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Accept", "application/json")
	req.Header.Set("X-Agent-Token", token)

	httpClient := &http.Client{
		Timeout: 10 * time.Second,
	}

	resp, err := httpClient.Do(req)
	if err != nil {
		return nil, fmt.Errorf("error de conexión con el servidor (%s): %w", url, err)
	}
	defer resp.Body.Close()

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return nil, fmt.Errorf("error leyendo respuesta del servidor: %w", err)
	}

	var apiResp ApiResponse
	if err := json.Unmarshal(body, &apiResp); err != nil {
		return nil, fmt.Errorf("respuesta inválida del servidor (Código %d): %s", resp.StatusCode, string(body))
	}

	if resp.StatusCode != http.StatusOK && resp.StatusCode != http.StatusCreated {
		errMsg := apiResp.Message
		if len(apiResp.Errors) > 0 {
			errMsg = fmt.Sprintf("%s - Detalles: %s", errMsg, string(apiResp.Errors))
		}
		return &apiResp, fmt.Errorf("servidor rechazó el registro (HTTP %d): %s", resp.StatusCode, errMsg)
	}

	return &apiResp, nil
}
