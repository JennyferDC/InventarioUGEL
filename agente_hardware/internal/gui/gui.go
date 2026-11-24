package gui

import (
	"fmt"
	"os"
	"runtime"
	"strings"
	"sync"
	"syscall"
	"time"
	"unsafe"

	"ugel-inventario-agente/internal/client"
	"ugel-inventario-agente/internal/hardware"
	"ugel-inventario-agente/internal/persona"
	"ugel-inventario-agente/internal/processor"
	"ugel-inventario-agente/internal/programs"
)

const (
	WM_DESTROY        = 0x0002
	WM_PAINT          = 0x000F
	WM_CLOSE          = 0x0010
	WM_DRAWITEM       = 0x002B
	WM_SETFONT        = 0x0030
	WM_SETICON        = 0x0080
	WM_COMMAND        = 0x0111
	WM_CTLCOLOREDIT   = 0x0133
	WM_CTLCOLORSTATIC = 0x0138
	WM_USER           = 0x0400

	// Custom user messages for async UI communication
	WM_APP_STATUS_UPDATE   = WM_USER + 101
	WM_APP_SHOW_WARNING    = WM_USER + 102
	WM_APP_SHOW_ERROR      = WM_USER + 103
	WM_APP_SUCCESS         = WM_USER + 104
	WM_APP_OFFICES_UPDATED = WM_USER + 105
	WM_APP_PROGRESS_UPDATE = WM_USER + 106

	// Window & control styles
	WS_OVERLAPPED    = 0x00000000
	WS_POPUP         = 0x80000000
	WS_CAPTION       = 0x00C00000
	WS_SYSMENU       = 0x00080000
	WS_MINIMIZEBOX   = 0x00020000
	WS_VISIBLE       = 0x10000000
	WS_CHILD         = 0x40000000
	WS_BORDER        = 0x00800000
	WS_TABSTOP       = 0x00010000
	WS_VSCROLL       = 0x00200000
	WS_EX_CLIENTEDGE = 0x00000200

	// Button styles
	BS_PUSHBUTTON    = 0x00000000
	BS_DEFPUSHBUTTON = 0x00000001
	BS_OWNERDRAW     = 0x0000000B
	BN_CLICKED       = 0

	// Owner draw item states
	ODS_SELECTED = 0x0001
	ODS_DISABLED = 0x0004
	ODS_FOCUS    = 0x0010

	// Edit styles & cue banner
	ES_AUTOHSCROLL  = 0x0080
	EM_SETCUEBANNER = 0x1501

	// ComboBox styles & messages
	CBS_DROPDOWNLIST = 0x0003
	CB_ADDSTRING     = 0x0143
	CB_SETCURSEL     = 0x014E
	CB_GETCURSEL     = 0x0147
	CB_RESETCONTENT  = 0x014B

	// Progress bar
	PBM_SETRANGE32 = WM_USER + 6
	PBM_SETPOS     = WM_USER + 2

	// MessageBox
	MB_OK          = 0x00000000
	MB_ICONINFO    = 0x00000040
	MB_ICONWARNING = 0x00000030
	MB_ICONERROR   = 0x00000010

	SW_HIDE = 0
	SW_SHOW = 5

	IDC_ARROW = 32512

	// IDs
	ID_BTN_REGISTRAR = 1001
	ID_BTN_CANCELAR  = 1002
	ID_TXT_NOMBRE    = 1003
	ID_CB_OFICINA    = 1004
	ID_TXT_CARGO     = 1005
	ID_TXT_CELULAR   = 1006
	ID_TXT_CORREO    = 1007
	ID_LBL_STATUS    = 1008
	ID_PROGRESS      = 1009
	ID_LBL_TITLE     = 1010
	ID_LBL_SUBTITLE  = 1011
	ID_LBL_BADGE     = 1012
	ID_BTN_ACEPTAR   = 2001
)

type RECT struct {
	Left   int32
	Top    int32
	Right  int32
	Bottom int32
}

type PAINTSTRUCT struct {
	Hdc         uintptr
	FErase      int32
	RcPaint     RECT
	FRestore    int32
	FIncUpdate  int32
	RgbReserved [32]byte
}

type DRAWITEMSTRUCT struct {
	CtlType    uint32
	CtlID      uint32
	ItemID     uint32
	ItemAction uint32
	ItemState  uint32
	HwndItem   uintptr
	Hdc        uintptr
	RcItem     RECT
	ItemData   uintptr
}

type WNDCLASSEXW struct {
	CbSize        uint32
	Style         uint32
	LpfnWndProc   uintptr
	CbClsExtra    int32
	CbWndExtra    int32
	HInstance     uintptr
	HIcon         uintptr
	HCursor       uintptr
	HbrBackground uintptr
	LpszMenuName  *uint16
	LpszClassName *uint16
	HIconSm       uintptr
}

type POINT struct {
	X, Y int32
}

type MSG struct {
	Hwnd    uintptr
	Message uint32
	WParam  uintptr
	LParam  uintptr
	Time    uint32
	Pt      POINT
}

type INITCOMMONCONTROLSEX struct {
	DwSize uint32
	DwICC  uint32
}

var (
	user32   = syscall.NewLazyDLL("user32.dll")
	kernel32 = syscall.NewLazyDLL("kernel32.dll")
	gdi32    = syscall.NewLazyDLL("gdi32.dll")
	comctl32 = syscall.NewLazyDLL("comctl32.dll")

	pRegisterClassExW     = user32.NewProc("RegisterClassExW")
	pCreateWindowExW      = user32.NewProc("CreateWindowExW")
	pDefWindowProcW       = user32.NewProc("DefWindowProcW")
	pShowWindow           = user32.NewProc("ShowWindow")
	pUpdateWindow         = user32.NewProc("UpdateWindow")
	pGetMessageW          = user32.NewProc("GetMessageW")
	pTranslateMessage     = user32.NewProc("TranslateMessage")
	pDispatchMessageW     = user32.NewProc("DispatchMessageW")
	pPostQuitMessage      = user32.NewProc("PostQuitMessage")
	pSendMessageW         = user32.NewProc("SendMessageW")
	pPostMessageW         = user32.NewProc("PostMessageW")
	pGetWindowTextW       = user32.NewProc("GetWindowTextW")
	pSetWindowTextW       = user32.NewProc("SetWindowTextW")
	pGetWindowTextLengthW = user32.NewProc("GetWindowTextLengthW")
	pEnableWindow         = user32.NewProc("EnableWindow")
	pMessageBoxW          = user32.NewProc("MessageBoxW")
	pDestroyWindow        = user32.NewProc("DestroyWindow")
	pSetFocus             = user32.NewProc("SetFocus")
	pSetForegroundWindow  = user32.NewProc("SetForegroundWindow")
	pLoadCursorW          = user32.NewProc("LoadCursorW")
	pGetSystemMetrics     = user32.NewProc("GetSystemMetrics")
	pSetProcessDPIAware   = user32.NewProc("SetProcessDPIAware")
	pIsDialogMessageW     = user32.NewProc("IsDialogMessageW")
	pBeginPaint           = user32.NewProc("BeginPaint")
	pEndPaint             = user32.NewProc("EndPaint")
	pFillRect             = user32.NewProc("FillRect")
	pDrawTextW            = user32.NewProc("DrawTextW")
	pInvalidateRect       = user32.NewProc("InvalidateRect")
	pGetWindowRect        = user32.NewProc("GetWindowRect")
	pIsWindow             = user32.NewProc("IsWindow")

	pGetModuleHandleW = kernel32.NewProc("GetModuleHandleW")
	pGetConsoleWindow = kernel32.NewProc("GetConsoleWindow")

	pCreateFontW      = gdi32.NewProc("CreateFontW")
	pDeleteObject     = gdi32.NewProc("DeleteObject")
	pSetBkMode        = gdi32.NewProc("SetBkMode")
	pSetTextColor     = gdi32.NewProc("SetTextColor")
	pCreateSolidBrush = gdi32.NewProc("CreateSolidBrush")
	pCreatePen        = gdi32.NewProc("CreatePen")
	pSelectObject     = gdi32.NewProc("SelectObject")
	pRoundRect        = gdi32.NewProc("RoundRect")
	pEllipse          = gdi32.NewProc("Ellipse")

	pInitCommonControlsEx = comctl32.NewProc("InitCommonControlsEx")
)

type GUIApp struct {
	ServerURL  string
	AgentToken string
	AutoDelete bool
	DebugMode  bool
	AutoTest   bool

	hwnd          uintptr
	imageMgr      *ImageManager
	hBgBrush      uintptr // Slate 50 (#F8FAFC)
	hWhiteBrush   uintptr // White (#FFFFFF)
	hBadgeBrush   uintptr // Blue 50 (#EFF6FF)
	hBadgePen     uintptr // Blue 200 (#BFDBFE)
	hGuindaBrush  uintptr // Guinda UGEL (#7D0A0A)
	hAzulBrush    uintptr // Azul UGEL (#1E3A8A)
	hDividerBrush uintptr // Gray 200 (#E5E7EB)

	hFontHeaderTitle uintptr
	hFontHeaderSub   uintptr
	hFontSection     uintptr
	hFontBold        uintptr
	hFontNormal      uintptr
	hFontSmall       uintptr
	hFontBadge       uintptr

	hTxtNombre    uintptr
	hCbOficina    uintptr
	hTxtCargo     uintptr
	hTxtCelular   uintptr
	hTxtCorreo    uintptr
	hLblStatus    uintptr
	hProgress     uintptr
	hBtnRegistrar uintptr
	hBtnCancelar  uintptr
	hLblBadge     uintptr

	oficinas []persona.Oficina

	mu                 sync.Mutex
	statusText         string
	warningText        string
	errorText          string
	successText        string
	successResponsable string
	successOficina     string
	successEstado      string
	isWorking          bool
}

var (
	currentApp *GUIApp
	logFile    *os.File
	logFileMu  sync.Mutex
)

func logMsg(format string, a ...interface{}) {
	ts := time.Now().Format("15:04:05.000")
	line := fmt.Sprintf("[%s] %s", ts, fmt.Sprintf(format, a...))
	fmt.Println(line)
	logFileMu.Lock()
	if logFile != nil {
		_, _ = logFile.WriteString(line + "\r\n")
		_ = logFile.Sync()
	}
	logFileMu.Unlock()
}

func rgb(r, g, b uint8) uintptr {
	return uintptr(uint32(r) | (uint32(g) << 8) | (uint32(b) << 16))
}

func createFont(name string, size int, bold bool) uintptr {
	weight := 400
	if bold {
		weight = 700
	}
	fontNamePtr := syscall.StringToUTF16Ptr(name)
	hFont, _, _ := pCreateFontW.Call(
		uintptr(-size),
		0, 0, 0,
		uintptr(weight),
		0, 0, 0,
		1, // DEFAULT_CHARSET
		0, 0,
		5, // CLEARTYPE_QUALITY
		0,
		uintptr(unsafe.Pointer(fontNamePtr)),
	)
	return hFont
}

func getWindowText(h uintptr) string {
	length, _, _ := pGetWindowTextLengthW.Call(h)
	if length == 0 {
		return ""
	}
	buf := make([]uint16, length+1)
	pGetWindowTextW.Call(h, uintptr(unsafe.Pointer(&buf[0])), uintptr(length+1))
	return syscall.UTF16ToString(buf)
}

func setWindowText(h uintptr, text string) {
	pSetWindowTextW.Call(h, uintptr(unsafe.Pointer(syscall.StringToUTF16Ptr(text))))
}

func setCueBanner(h uintptr, text string) {
	pSendMessageW.Call(h, EM_SETCUEBANNER, 1, uintptr(unsafe.Pointer(syscall.StringToUTF16Ptr(text))))
}

func showMessageBox(h uintptr, title, text string, flags uint32) {
	pMessageBoxW.Call(
		h,
		uintptr(unsafe.Pointer(syscall.StringToUTF16Ptr(text))),
		uintptr(unsafe.Pointer(syscall.StringToUTF16Ptr(title))),
		uintptr(flags),
	)
}

func addComboItem(h uintptr, text string) {
	pSendMessageW.Call(h, CB_ADDSTRING, 0, uintptr(unsafe.Pointer(syscall.StringToUTF16Ptr(text))))
}

func getComboCurSel(h uintptr) int {
	sel, _, _ := pSendMessageW.Call(h, CB_GETCURSEL, 0, 0)
	return int(int32(sel))
}

func setComboCurSel(h uintptr, idx int) {
	pSendMessageW.Call(h, CB_SETCURSEL, uintptr(idx), 0)
}

func (app *GUIApp) setStatus(text string) {
	app.mu.Lock()
	app.statusText = text
	app.mu.Unlock()
	pPostMessageW.Call(app.hwnd, WM_APP_STATUS_UPDATE, 0, 0)
}

func (app *GUIApp) showWarning(text string) {
	app.mu.Lock()
	app.warningText = text
	app.mu.Unlock()
	pPostMessageW.Call(app.hwnd, WM_APP_SHOW_WARNING, 0, 0)
}

func (app *GUIApp) showError(text string) {
	app.mu.Lock()
	app.errorText = text
	app.mu.Unlock()
	pPostMessageW.Call(app.hwnd, WM_APP_SHOW_ERROR, 0, 0)
}

func (app *GUIApp) showSuccess(text string) {
	app.mu.Lock()
	app.successText = text
	app.mu.Unlock()
	pPostMessageW.Call(app.hwnd, WM_APP_SUCCESS, 0, 0)
}

func (app *GUIApp) setProgress(pos int) {
	pPostMessageW.Call(app.hwnd, WM_APP_PROGRESS_UPDATE, uintptr(pos), 0)
}

func (app *GUIApp) setControlsEnabled(enabled bool) {
	val := uintptr(0)
	if enabled {
		val = 1
	}
	pEnableWindow.Call(app.hBtnRegistrar, val)
	pEnableWindow.Call(app.hBtnCancelar, val)
	pEnableWindow.Call(app.hTxtNombre, val)
	pEnableWindow.Call(app.hCbOficina, val)
	pEnableWindow.Call(app.hTxtCargo, val)
	pEnableWindow.Call(app.hTxtCelular, val)
	pEnableWindow.Call(app.hTxtCorreo, val)

	// Forzar repintado de botones personalizados para actualizar su estado visual
	pInvalidateRect.Call(app.hBtnRegistrar, 0, 1)
	pInvalidateRect.Call(app.hBtnCancelar, 0, 1)

	if enabled {
		pShowWindow.Call(app.hProgress, SW_HIDE)
		pSendMessageW.Call(app.hProgress, PBM_SETPOS, 0, 0)
		app.setStatus("Listo. Complete sus datos y haga clic en 'Registrar y Enviar Equipo'.")
	} else {
		pShowWindow.Call(app.hProgress, SW_SHOW)
		pSendMessageW.Call(app.hProgress, PBM_SETRANGE32, 0, 100)
		pSendMessageW.Call(app.hProgress, PBM_SETPOS, 10, 0)
	}
}

// Dibujar botones con estilo moderno redondeado e identidad visual institucional UGEL
func (app *GUIApp) drawCustomButton(dis *DRAWITEMSTRUCT) {
	hdc := dis.Hdc
	rc := dis.RcItem

	isPressed := (dis.ItemState & ODS_SELECTED) != 0
	isDisabled := (dis.ItemState & ODS_DISABLED) != 0

	var bgCol, borderCol, textCol uintptr
	var text string
	var hFont uintptr

	if dis.CtlID == ID_BTN_REGISTRAR {
		text = "Registrar y Enviar Equipo"
		hFont = app.hFontBold
		if isDisabled {
			bgCol = rgb(148, 163, 184)     // Slate 400
			borderCol = rgb(148, 163, 184)
			textCol = rgb(241, 245, 249)   // Slate 100
		} else if isPressed {
			bgCol = rgb(125, 10, 10)       // Guinda UGEL #7D0A0A (efecto click institucional)
			borderCol = rgb(125, 10, 10)
			textCol = rgb(255, 255, 255)
		} else {
			bgCol = rgb(30, 58, 138)       // Azul Institucional UGEL #1E3A8A
			borderCol = rgb(30, 58, 138)
			textCol = rgb(255, 255, 255)
		}
	} else if dis.CtlID == ID_BTN_CANCELAR {
		text = "Cerrar"
		hFont = app.hFontNormal
		if isDisabled {
			bgCol = rgb(248, 250, 252)
			borderCol = rgb(226, 232, 240)
			textCol = rgb(148, 163, 184)
		} else if isPressed {
			bgCol = rgb(241, 245, 249)     // Slate 100
			borderCol = rgb(148, 163, 184)
			textCol = rgb(15, 23, 42)
		} else {
			bgCol = rgb(255, 255, 255)     // White
			borderCol = rgb(203, 213, 225) // Slate 300
			textCol = rgb(51, 65, 85)      // Slate 700
		}
	}

	hBrush, _, _ := pCreateSolidBrush.Call(bgCol)
	hPen, _, _ := pCreatePen.Call(0, 1, borderCol)

	hOldBrush, _, _ := pSelectObject.Call(hdc, hBrush)
	hOldPen, _, _ := pSelectObject.Call(hdc, hPen)

	// Esquinas suavemente redondeadas (radio 8px) idéntico a rounded-lg en Tailwind
	pRoundRect.Call(hdc, uintptr(rc.Left), uintptr(rc.Top), uintptr(rc.Right), uintptr(rc.Bottom), 8, 8)

	pSelectObject.Call(hdc, hOldBrush)
	pSelectObject.Call(hdc, hOldPen)
	pDeleteObject.Call(hBrush)
	pDeleteObject.Call(hPen)

	// Dibujar texto con tipografía institucional
	pSetBkMode.Call(hdc, 1) // TRANSPARENT
	pSetTextColor.Call(hdc, textCol)
	if hFont != 0 {
		hOldFont, _, _ := pSelectObject.Call(hdc, hFont)
		defer pSelectObject.Call(hdc, hOldFont)
	}

	textPtr := syscall.StringToUTF16Ptr(text)
	pDrawTextW.Call(
		hdc,
		uintptr(unsafe.Pointer(textPtr)),
		uintptr(len(text)),
		uintptr(unsafe.Pointer(&rc)),
		0x00000001|0x00000004|0x00000020, // DT_CENTER | DT_VCENTER | DT_SINGLELINE
	)
}

// Pintar fondo institucional, cabecera con logotipo oficial y franja bicolor
func (app *GUIApp) paintWindow(hdc uintptr) {
	// 1. Fondo blanco puro del modal/tarjeta (idéntico a los modales de Inventario de Equipos)
	clientRc := RECT{Left: 0, Top: 0, Right: 500, Bottom: 580}
	pFillRect.Call(hdc, uintptr(unsafe.Pointer(&clientRc)), app.hWhiteBrush)

	// 2. Renderizar Logotipo Oficial UGEL Huánuco con antialiasing y canal alfa
	// Mantiene el tamaño oficial y proporciones (150x48 px, ratio 3.12:1)
	if app.imageMgr != nil {
		app.imageMgr.DrawLogo(hdc, 28, 18, 150, 48)
	}

	// 3. Subtítulo institucional y descripción (módulo Inventario de Equipos)
	pSetBkMode.Call(hdc, 1) // TRANSPARENT
	hOldFont, _, _ := pSelectObject.Call(hdc, app.hFontHeaderSub)
	pSetTextColor.Call(hdc, rgb(30, 58, 138)) // Azul UGEL #1E3A8A
	subText := syscall.StringToUTF16Ptr("SISTEMA DE INVENTARIO")
	rcSub := RECT{Left: 190, Top: 24, Right: 485, Bottom: 46}
	pDrawTextW.Call(hdc, uintptr(unsafe.Pointer(subText)), ^uintptr(0), uintptr(unsafe.Pointer(&rcSub)), 0x00000020)

	pSelectObject.Call(hdc, app.hFontSmall)
	pSetTextColor.Call(hdc, rgb(100, 116, 139)) // Slate 500
	descText := syscall.StringToUTF16Ptr("Auditoría Técnica y Registro de Equipos")
	rcDesc := RECT{Left: 190, Top: 46, Right: 485, Bottom: 66}
	pDrawTextW.Call(hdc, uintptr(unsafe.Pointer(descText)), ^uintptr(0), uintptr(unsafe.Pointer(&rcDesc)), 0x00000020)

	// 4. Franja de Acento Bicolor Institucional (3px de alto: Guinda a la izquierda, Azul a la derecha)
	rcGuinda := RECT{Left: 0, Top: 82, Right: 250, Bottom: 85}
	pFillRect.Call(hdc, uintptr(unsafe.Pointer(&rcGuinda)), app.hGuindaBrush)
	rcAzul := RECT{Left: 250, Top: 82, Right: 500, Bottom: 85}
	pFillRect.Call(hdc, uintptr(unsafe.Pointer(&rcAzul)), app.hAzulBrush)

	// 5. Marco contenedor redondeado del badge de equipo detectado (420px de ancho)
	hOldBrush, _, _ := pSelectObject.Call(hdc, app.hBadgeBrush)
	hOldPen, _, _ := pSelectObject.Call(hdc, app.hBadgePen)
	pRoundRect.Call(hdc, 40, 96, 460, 128, 6, 6)

	// 6. Título de sección idéntico al apartado de asignación de Inventario (Guinda en mayúsculas con línea divisoria)
	pSelectObject.Call(hdc, app.hFontSection)
	pSetTextColor.Call(hdc, rgb(125, 10, 10)) // Guinda UGEL #7D0A0A
	secText := syscall.StringToUTF16Ptr("ASIGNACIÓN DEL RESPONSABLE DEL EQUIPO")
	rcSec := RECT{Left: 40, Top: 138, Right: 460, Bottom: 154}
	pDrawTextW.Call(hdc, uintptr(unsafe.Pointer(secText)), ^uintptr(0), uintptr(unsafe.Pointer(&rcSec)), 0x00000020)

	// Línea divisoria de sección (Gray 200 #E5E7EB)
	rcSecLine := RECT{Left: 40, Top: 156, Right: 460, Bottom: 157}
	pFillRect.Call(hdc, uintptr(unsafe.Pointer(&rcSecLine)), app.hDividerBrush)

	// 7. Línea divisoria del pie de página (sobre los botones) idéntica a border-t border-ugel-azul/10 de los modales
	rcFooterLine := RECT{Left: 40, Top: 472, Right: 460, Bottom: 473}
	pFillRect.Call(hdc, uintptr(unsafe.Pointer(&rcFooterLine)), app.hDividerBrush)

	pSelectObject.Call(hdc, hOldBrush)
	pSelectObject.Call(hdc, hOldPen)
	pSelectObject.Call(hdc, hOldFont)
}

func wndProc(hwnd uintptr, msg uint32, wParam, lParam uintptr) uintptr {
	if currentApp == nil {
		ret, _, _ := pDefWindowProcW.Call(hwnd, uintptr(msg), wParam, lParam)
		return ret
	}

	switch msg {
	case WM_PAINT:
		var ps PAINTSTRUCT
		hdc, _, _ := pBeginPaint.Call(hwnd, uintptr(unsafe.Pointer(&ps)))
		if hdc != 0 {
			currentApp.paintWindow(hdc)
			pEndPaint.Call(hwnd, uintptr(unsafe.Pointer(&ps)))
			return 0
		}

	case WM_DRAWITEM:
		dis := (*DRAWITEMSTRUCT)(unsafe.Pointer(lParam))
		if dis.CtlID == ID_BTN_REGISTRAR || dis.CtlID == ID_BTN_CANCELAR {
			currentApp.drawCustomButton(dis)
			return 1
		}

	case WM_COMMAND:
		ctrlID := uint16(wParam & 0xFFFF)
		notifyCode := uint16((wParam >> 16) & 0xFFFF)
		if notifyCode == BN_CLICKED {
			if ctrlID == ID_BTN_REGISTRAR {
				currentApp.onRegistrarClicked()
			} else if ctrlID == ID_BTN_CANCELAR {
				pDestroyWindow.Call(hwnd)
			}
		}
		return 0

	case WM_CTLCOLORSTATIC:
		ctrlHwnd := lParam
		if ctrlHwnd == currentApp.hLblBadge {
			pSetTextColor.Call(wParam, rgb(30, 58, 138)) // Azul UGEL #1E3A8A
			pSetBkMode.Call(wParam, 1)                   // TRANSPARENT
			return currentApp.hBadgeBrush
		} else if ctrlHwnd == currentApp.hLblStatus {
			currentApp.mu.Lock()
			working := currentApp.isWorking
			currentApp.mu.Unlock()
			if working {
				pSetTextColor.Call(wParam, rgb(30, 58, 138)) // Azul UGEL en progreso
			} else {
				pSetTextColor.Call(wParam, rgb(107, 114, 128)) // Gray 500 (#6B7280)
			}
			pSetBkMode.Call(wParam, 1)
			return currentApp.hWhiteBrush
		} else {
			pSetTextColor.Call(wParam, rgb(55, 65, 81)) // Gray 700 (#374151) para etiquetas de Inventario
			pSetBkMode.Call(wParam, 1)
			return currentApp.hWhiteBrush
		}

	case WM_CTLCOLOREDIT:
		pSetTextColor.Call(wParam, rgb(31, 41, 55)) // Gray 800 (#1F2937)
		pSetBkMode.Call(wParam, 1)
		return currentApp.hWhiteBrush

	case WM_APP_STATUS_UPDATE:
		currentApp.mu.Lock()
		text := currentApp.statusText
		currentApp.mu.Unlock()
		setWindowText(currentApp.hLblStatus, text)
		return 0

	case WM_APP_PROGRESS_UPDATE:
		pSendMessageW.Call(currentApp.hProgress, PBM_SETPOS, wParam, 0)
		return 0

	case WM_APP_OFFICES_UPDATED:
		currentApp.mu.Lock()
		curSel := getComboCurSel(currentApp.hCbOficina)
		ofis := currentApp.oficinas
		currentApp.mu.Unlock()

		if curSel <= 0 && len(ofis) > 0 {
			pSendMessageW.Call(currentApp.hCbOficina, CB_RESETCONTENT, 0, 0)
			addComboItem(currentApp.hCbOficina, "-- Seleccione su oficina --")
			for _, o := range ofis {
				itemStr := o.Nombre
				if o.AreaNombre != "" {
					itemStr = fmt.Sprintf("%s (%s)", o.Nombre, o.AreaNombre)
				}
				addComboItem(currentApp.hCbOficina, itemStr)
			}
			setComboCurSel(currentApp.hCbOficina, 0)
		}
		return 0

	case WM_APP_SHOW_WARNING:
		logMsg("[UI] wndProc: WM_APP_SHOW_WARNING recibido.")
		currentApp.mu.Lock()
		msg := currentApp.warningText
		autoTest := currentApp.AutoTest
		currentApp.isWorking = false
		currentApp.mu.Unlock()
		currentApp.setControlsEnabled(true)
		if !autoTest {
			showMessageBox(hwnd, "Alerta de InventarioUGEL", msg, MB_OK|MB_ICONWARNING)
		} else {
			logMsg("[AUTO-TEST] Alerta mostrada:\n%s", msg)
			pDestroyWindow.Call(hwnd)
		}
		return 0

	case WM_APP_SHOW_ERROR:
		logMsg("[UI] wndProc: WM_APP_SHOW_ERROR recibido.")
		currentApp.mu.Lock()
		msg := currentApp.errorText
		autoTest := currentApp.AutoTest
		currentApp.isWorking = false
		currentApp.mu.Unlock()
		currentApp.setControlsEnabled(true)
		if !autoTest {
			showMessageBox(hwnd, "Error de Registro", msg, MB_OK|MB_ICONERROR)
		} else {
			logMsg("[AUTO-TEST] Error de registro:\n%s", msg)
			pDestroyWindow.Call(hwnd)
		}
		return 0

	case WM_APP_SUCCESS:
		logMsg("[UI] wndProc: WM_APP_SUCCESS recibido.")
		currentApp.mu.Lock()
		autoTest := currentApp.AutoTest
		resp := currentApp.successResponsable
		ofi := currentApp.successOficina
		est := currentApp.successEstado
		currentApp.isWorking = false
		currentApp.mu.Unlock()
		currentApp.setControlsEnabled(true)
		if !autoTest {
			// Cerrar y ocultar la ventana del formulario para mostrar únicamente la confirmación
			pShowWindow.Call(hwnd, SW_HIDE)
			showSuccessDialog(hwnd, resp, ofi, est)
		} else {
			logMsg("[AUTO-TEST] ¡Éxito confirmado! Responsable: %s, Oficina: %s, Estado: %s", resp, ofi, est)
		}

		if currentApp.AutoDelete && !autoTest {
			_ = ScheduleSelfDelete()
		}
		pDestroyWindow.Call(hwnd)
		return 0

	case WM_CLOSE:
		currentApp.mu.Lock()
		working := currentApp.isWorking
		currentApp.mu.Unlock()
		if working {
			showMessageBox(hwnd, "Operación en curso", "La auditoría y envío de datos están en proceso. Espere unos momentos.", MB_OK|MB_ICONWARNING)
			return 0
		}
		pDestroyWindow.Call(hwnd)
		return 0

	case WM_DESTROY:
		logMsg("[UI] wndProc: WM_DESTROY recibido.")
		pPostQuitMessage.Call(0)
		return 0
	}

	ret, _, _ := pDefWindowProcW.Call(hwnd, uintptr(msg), wParam, lParam)
	return ret
}

func (app *GUIApp) onRegistrarClicked() {
	logMsg("[UI] onRegistrarClicked() ejecutado.")
	app.mu.Lock()
	if app.isWorking {
		logMsg("[UI] Ya está en progreso. Ignorando.")
		app.mu.Unlock()
		return
	}
	app.mu.Unlock()

	// 1. Extraer valores del formulario (respetando campos exactos de InventarioUGEL)
	nombreRaw := strings.TrimSpace(getWindowText(app.hTxtNombre))
	oficinaIdx := getComboCurSel(app.hCbOficina)
	cargoRaw := strings.TrimSpace(getWindowText(app.hTxtCargo))
	celularRaw := strings.TrimSpace(getWindowText(app.hTxtCelular))
	correoRaw := strings.TrimSpace(getWindowText(app.hTxtCorreo))

	logMsg("[UI] Valores formulario: Nombre='%s', OficinaIdx=%d, Cargo='%s', Celular='%s', Correo='%s'",
		nombreRaw, oficinaIdx, cargoRaw, celularRaw, correoRaw)

	// 2. Validaciones con alertas institucionales claras
	nombreFormateado := persona.FormatNombreCompleto(nombreRaw)
	if nombreFormateado == "" {
		logMsg("[UI] Validación fallida: Nombre completo vacío.")
		showMessageBox(app.hwnd, "Campo Obligatorio", "Por favor ingrese el Nombre completo del responsable.", MB_OK|MB_ICONWARNING)
		pSetFocus.Call(app.hTxtNombre)
		return
	}

	if oficinaIdx <= 0 || oficinaIdx > len(app.oficinas) {
		logMsg("[UI] Validación fallida: Oficina no seleccionada.")
		showMessageBox(app.hwnd, "Campo Obligatorio", "Por favor seleccione la Oficina del responsable de la lista.", MB_OK|MB_ICONWARNING)
		pSetFocus.Call(app.hCbOficina)
		return
	}

	selectedOficina := app.oficinas[oficinaIdx-1]
	logMsg("[UI] Oficina seleccionada: ID=%d, Nombre='%s', Area='%s'", selectedOficina.ID, selectedOficina.Nombre, selectedOficina.AreaNombre)

	var celularFinal string
	if celularRaw != "" {
		digits := persona.CleanCelularDigits(celularRaw)
		if len(digits) != 9 {
			logMsg("[UI] Validación fallida: Celular no tiene 9 dígitos ('%s').", celularRaw)
			showMessageBox(app.hwnd, "Celular Inválido", "El número de celular debe contener exactamente 9 dígitos numéricos.", MB_OK|MB_ICONWARNING)
			pSetFocus.Call(app.hTxtCelular)
			return
		}
		celularFinal = digits
	}

	var correoFinal string
	if correoRaw != "" {
		correoClean := persona.CleanCorreo(correoRaw)
		if !persona.IsValidCorreo(correoClean) {
			logMsg("[UI] Validación fallida: Correo inválido ('%s').", correoRaw)
			showMessageBox(app.hwnd, "Correo Inválido", "El correo ingresado no tiene un formato válido (ejemplo: correo@ejemplo.com).", MB_OK|MB_ICONWARNING)
			pSetFocus.Call(app.hTxtCorreo)
			return
		}
		correoFinal = correoClean
	}

	var cargoFinal string
	if cargoRaw != "" {
		cargoFinal = persona.FormatNombreCompleto(cargoRaw)
	}

	// Construir modelo de persona respetando campos de InventarioUGEL
	usuario := &persona.Persona{
		NombreCompleto: nombreFormateado,
		IDOficina:      selectedOficina.ID,
		Cargo:          cargoFinal,
		Celular:        celularFinal,
		Correo:         correoFinal,
		Estado:         "ACTIVO",
	}

	// Bloquear controles e iniciar proceso pesado en segundo plano
	app.mu.Lock()
	app.isWorking = true
	app.mu.Unlock()
	logMsg("[UI] Deshabilitando controles y mostrando barra de progreso...")
	app.setControlsEnabled(false)

	app.setStatus("Paso 1/3: Recopilando características técnicas de hardware...")

	logMsg("[UI] Lanzando goroutine de auditoría en segundo plano...")
	go func() {
		defer func() {
			if r := recover(); r != nil {
				logMsg("[ERROR] Panic recuperado en goroutine de auditoría: %v", r)
				app.showError(fmt.Sprintf("Error crítico durante la auditoría: %v", r))
			}
		}()

		// PASO 1: Recopilación de Hardware en segundo plano
		app.setStatus("Paso 1/3: Recopilando características técnicas de hardware...")
		app.setProgress(15)
		logMsg("[UI] [Paso 1/3] Ejecutando hardware.CollectHardware()...")
		startHw := time.Now()
		hwReport, errHw := hardware.CollectHardware()
		logMsg("[UI] [Paso 1/3] hardware.CollectHardware() finalizado en %v (err=%v)", time.Since(startHw), errHw)
		if errHw != nil {
			app.showError(fmt.Sprintf("Error al extraer el hardware de la computadora:\n\n%v", errHw))
			return
		}
		app.setProgress(45)

		// PASO 2: Detección de programas instalados en segundo plano
		app.setStatus("Paso 2/3: Escaneando software y programas instalados...")
		logMsg("[UI] [Paso 2/3] Ejecutando programs.CollectInstalledPrograms()...")
		startProg := time.Now()
		progs, errProg := programs.CollectInstalledPrograms()
		logMsg("[UI] [Paso 2/3] programs.CollectInstalledPrograms() finalizado en %v (%d programas, err=%v)", time.Since(startProg), len(progs), errProg)
		if errProg != nil {
			progs = make([]programs.InstalledProgram, 0)
		}
		app.setProgress(70)

		// PASO 3: Ensamblado de payload unificado
		app.setStatus("Paso 3/3: Transmitiendo datos a InventarioUGEL...")
		app.setProgress(85)
		logMsg("[UI] [Paso 3/3] Ensamblando payload unificado...")
		payload, errPayload := processor.BuildUnifiedPayload(usuario, hwReport, progs)
		if errPayload != nil {
			logMsg("[UI] Error estructurando payload: %v", errPayload)
			app.showError(fmt.Sprintf("Error estructurando la información del equipo:\n\n%v", errPayload))
			return
		}

		// PASO 4: Envío a la API REST de InventarioUGEL
		logMsg("[UI] [Paso 4/3] Transmitiendo a la API (%s/api/v1/agente/registro)...", app.ServerURL)
		startSend := time.Now()
		apiResp, errSend := client.SendRegistration(app.ServerURL, app.AgentToken, payload)
		logMsg("[UI] [Paso 4/3] Envío a API finalizado en %v (err=%v)", time.Since(startSend), errSend)

		if errSend != nil {
			if apiResp != nil && !apiResp.Success {
				logMsg("[UI] Servidor retornó conflicto de validación: %s", apiResp.Message)
				app.showWarning(apiResp.Message)
			} else {
				logMsg("[UI] Error de red: %v", errSend)
				app.showError(fmt.Sprintf("No se pudo conectar con el servidor de InventarioUGEL (%s):\n\n%v\n\n"+
					"Compruebe que el servidor esté activo o que su PC tenga conexión a la red.", app.ServerURL, errSend))
			}
			return
		}

		app.setProgress(100)

		// PASO 5: Confirmación de éxito
		logMsg("[UI] ¡Registro completado exitosamente! Acción=%s", apiResp.Action)
		app.mu.Lock()
		app.successResponsable = usuario.NombreCompleto
		if selectedOficina.AreaNombre != "" && !strings.EqualFold(selectedOficina.Nombre, selectedOficina.AreaNombre) {
			app.successOficina = fmt.Sprintf("%s (%s)", selectedOficina.Nombre, selectedOficina.AreaNombre)
		} else {
			app.successOficina = selectedOficina.Nombre
		}
		app.successEstado = "Revisión"
		app.mu.Unlock()

		app.showSuccess("")
	}()
}

// HideConsoleWindow hides the standard CMD prompt window when running in GUI mode
func HideConsoleWindow() {
	if pGetConsoleWindow != nil {
		hConsole, _, _ := pGetConsoleWindow.Call()
		if hConsole != 0 {
			pShowWindow.Call(hConsole, SW_HIDE)
		}
	}
}

// RunGUI initializes and displays the native Windows GUI form instantaneously (< 50ms)
func RunGUI(serverURL, token string, autoDelete, debugMode, autoTest bool) error {
	runtime.LockOSThread()
	defer runtime.UnlockOSThread()

	var errLog error
	logFile, errLog = os.OpenFile("agente_debug.log", os.O_CREATE|os.O_WRONLY|os.O_TRUNC, 0666)
	if errLog == nil {
		logMsg("--- INICIANDO AGENTE UGEL GUI (DISEÑO MODERNO INSTITUCIONAL) ---")
	}

	if !debugMode && !autoTest {
		HideConsoleWindow()
	} else {
		logMsg("[DEBUG] Modo consola visible activado.")
	}

	logMsg("[INIT] Paso 1: SetProcessDPIAware...")
	pSetProcessDPIAware.Call()

	logMsg("[INIT] Paso 2: InitCommonControlsEx...")
	icex := INITCOMMONCONTROLSEX{
		DwSize: uint32(unsafe.Sizeof(INITCOMMONCONTROLSEX{})),
		DwICC:  0x00000001 | 0x00000020, // ICC_STANDARD_CLASSES | ICC_PROGRESS_CLASS
	}
	pInitCommonControlsEx.Call(uintptr(unsafe.Pointer(&icex)))

	logMsg("[INIT] Paso 3: Inicializando GDI+ y recursos de marca institucional...")
	imgMgr, errImg := initGdiplus()
	if errImg != nil {
		logMsg("[WARN] No se pudo inicializar GDI+: %v", errImg)
	}

	logMsg("[INIT] Paso 4: RegisterClassExW...")
	hInstance, _, _ := pGetModuleHandleW.Call(0)
	className := syscall.StringToUTF16Ptr("UGEL_Inventario_GUI_Class")
	windowTitle := syscall.StringToUTF16Ptr("Registro de Equipos de Cómputo")

	hCursor, _, _ := pLoadCursorW.Call(0, uintptr(IDC_ARROW))

	// Paleta de colores institucionales UGEL e Inventario de Equipos
	hWhiteBrush, _, _ := pCreateSolidBrush.Call(rgb(255, 255, 255))   // Blanco puro (#FFFFFF) idéntico a modales
	hBadgeBrush, _, _ := pCreateSolidBrush.Call(rgb(239, 246, 255))   // Blue 50 (#EFF6FF)
	hBadgePen, _, _ := pCreatePen.Call(0, 1, rgb(191, 219, 254))      // Blue 200 (#BFDBFE)
	hGuindaBrush, _, _ := pCreateSolidBrush.Call(rgb(125, 10, 10))     // Guinda UGEL (#7D0A0A)
	hAzulBrush, _, _ := pCreateSolidBrush.Call(rgb(30, 58, 138))       // Azul Institucional (#1E3A8A)
	hDividerBrush, _, _ := pCreateSolidBrush.Call(rgb(229, 231, 235)) // Gray 200 (#E5E7EB)

	var hClassIcon uintptr
	var hClassIconSm uintptr
	if imgMgr != nil {
		hClassIcon = imgMgr.hTaskbarIcon
		hClassIconSm = imgMgr.hTransparentIcon
	}

	wc := WNDCLASSEXW{
		CbSize:        uint32(unsafe.Sizeof(WNDCLASSEXW{})),
		Style:         0x0003, // CS_HREDRAW | CS_VREDRAW
		LpfnWndProc:   syscall.NewCallback(wndProc),
		HInstance:     hInstance,
		HIcon:         hClassIcon,
		HCursor:       hCursor,
		HbrBackground: hWhiteBrush,
		LpszClassName: className,
		HIconSm:       hClassIconSm,
	}

	pRegisterClassExW.Call(uintptr(unsafe.Pointer(&wc)))

	logMsg("[INIT] Paso 5: CreateWindowExW (Ventana principal compacta 500x580)...")
	winWidth := 500
	winHeight := 580
	screenWidth, _, _ := pGetSystemMetrics.Call(0)
	screenHeight, _, _ := pGetSystemMetrics.Call(1)
	posX := (int(screenWidth) - winWidth) / 2
	posY := (int(screenHeight) - winHeight) / 2

	hwnd, _, _ := pCreateWindowExW.Call(
		0x00000001, // WS_EX_DLGMODALFRAME (elimina el icono de la esquina superior izquierda del título)
		uintptr(unsafe.Pointer(className)),
		uintptr(unsafe.Pointer(windowTitle)),
		WS_OVERLAPPED|WS_CAPTION|WS_SYSMENU|WS_MINIMIZEBOX,
		uintptr(posX),
		uintptr(posY),
		uintptr(winWidth),
		uintptr(winHeight),
		0, 0,
		hInstance,
		0,
	)

	if hwnd == 0 {
		return fmt.Errorf("no se pudo crear la ventana gráfica principal")
	}

	// Configurar iconos:
	// - Barra de tareas: icono minimalista de computadora con fondo azul institucional
	// - Esquina superior izquierda (título): icono 100% transparente para que no aparezca nada
	if imgMgr != nil {
		if imgMgr.hTransparentIcon != 0 {
			pSendMessageW.Call(hwnd, WM_SETICON, 0, imgMgr.hTransparentIcon) // ICON_SMALL (título)
		}
		if imgMgr.hTaskbarIcon != 0 {
			pSendMessageW.Call(hwnd, WM_SETICON, 1, imgMgr.hTaskbarIcon) // ICON_BIG (barra de tareas)
		}
	}

	logMsg("[INIT] Paso 6: Configurando tipografías y estructura GUIApp (HWND=%d)...", hwnd)
	app := &GUIApp{
		ServerURL:        serverURL,
		AgentToken:       token,
		AutoDelete:       autoDelete,
		DebugMode:        debugMode,
		AutoTest:         autoTest,
		hwnd:             hwnd,
		imageMgr:         imgMgr,
		hBgBrush:         hWhiteBrush,
		hWhiteBrush:      hWhiteBrush,
		hBadgeBrush:      hBadgeBrush,
		hBadgePen:        hBadgePen,
		hGuindaBrush:     hGuindaBrush,
		hAzulBrush:       hAzulBrush,
		hDividerBrush:    hDividerBrush,
		hFontHeaderTitle: createFont("Segoe UI", 16, true),
		hFontHeaderSub:   createFont("Segoe UI", 12, true),
		hFontSection:     createFont("Segoe UI", 8, true),
		hFontBold:        createFont("Segoe UI", 10, true),
		hFontNormal:      createFont("Segoe UI", 9, false),
		hFontSmall:       createFont("Segoe UI", 8, false),
		hFontBadge:       createFont("Segoe UI", 8, true),
	}
	currentApp = app

	createLabel := func(text string, x, y, w, h int, font uintptr, id int) uintptr {
		hl, _, _ := pCreateWindowExW.Call(
			0,
			uintptr(unsafe.Pointer(syscall.StringToUTF16Ptr("STATIC"))),
			uintptr(unsafe.Pointer(syscall.StringToUTF16Ptr(text))),
			WS_CHILD|WS_VISIBLE,
			uintptr(x), uintptr(y), uintptr(w), uintptr(h),
			hwnd, uintptr(id), hInstance, 0,
		)
		if font != 0 {
			pSendMessageW.Call(hl, WM_SETFONT, font, 1)
		}
		return hl
	}

	createEdit := func(id int, x, y, w, h int, cueBanner string) uintptr {
		he, _, _ := pCreateWindowExW.Call(
			WS_EX_CLIENTEDGE,
			uintptr(unsafe.Pointer(syscall.StringToUTF16Ptr("EDIT"))),
			0,
			WS_CHILD|WS_VISIBLE|WS_BORDER|WS_TABSTOP|ES_AUTOHSCROLL,
			uintptr(x), uintptr(y), uintptr(w), uintptr(h),
			hwnd, uintptr(id), hInstance, 0,
		)
		pSendMessageW.Call(he, WM_SETFONT, app.hFontNormal, 1)
		if cueBanner != "" {
			setCueBanner(he, cueBanner)
		}
		return he
	}

	logMsg("[INIT] Paso 7: Creando campos del formulario reutilizando diseño de Inventario...")

	// 1. BADGE DE EQUIPO DETECTADO (420px ancho, estilo badge Inventario)
	hostname, _ := os.Hostname()
	badgeText := fmt.Sprintf("Equipo detectado: %s  •  Auditoría técnica vinculada", hostname)
	app.hLblBadge = createLabel(badgeText, 50, 104, 400, 16, app.hFontBadge, ID_LBL_BADGE)

	// 2. CAMPO: NOMBRE COMPLETO (Obligatorio, idéntico a Inventario ModaCrear.vue)
	createLabel("Nombre completo *", 40, 168, 420, 16, app.hFontBold, 0)
	app.hTxtNombre = createEdit(ID_TXT_NOMBRE, 40, 186, 420, 26, "Ingrese nombres y apellidos")

	// 3. CAMPO: OFICINA (Obligatorio, idéntico a Inventario ModaCrear.vue)
	createLabel("Oficina *", 40, 220, 420, 16, app.hFontBold, 0)
	hCb, _, _ := pCreateWindowExW.Call(
		0,
		uintptr(unsafe.Pointer(syscall.StringToUTF16Ptr("COMBOBOX"))),
		0,
		WS_CHILD|WS_VISIBLE|WS_TABSTOP|CBS_DROPDOWNLIST|WS_VSCROLL,
		uintptr(40), uintptr(238), uintptr(420), uintptr(240),
		hwnd, uintptr(ID_CB_OFICINA), hInstance, 0,
	)
	pSendMessageW.Call(hCb, WM_SETFONT, app.hFontNormal, 1)
	app.hCbOficina = hCb

	// Carga inmediata del catálogo institucional integrado
	app.oficinas = persona.GetDefaultOficinas()
	addComboItem(hCb, "-- Seleccione su oficina --")
	for _, o := range app.oficinas {
		itemStr := o.Nombre
		if o.AreaNombre != "" {
			itemStr = fmt.Sprintf("%s (%s)", o.Nombre, o.AreaNombre)
		}
		addComboItem(hCb, itemStr)
	}
	setComboCurSel(hCb, 0)

	// Sincronización asíncrona de fondo con la API sin retrasar la visualización
	go func() {
		remoteOfis, err := client.FetchRemoteOficinas(serverURL)
		if err == nil && len(remoteOfis) > 0 {
			app.mu.Lock()
			app.oficinas = remoteOfis
			app.mu.Unlock()
			pPostMessageW.Call(app.hwnd, WM_APP_OFFICES_UPDATED, 0, 0)
		}
	}()

	// 4. CAMPO: CARGO / ESPECIALIDAD (Opcional, idéntico a Inventario ModaCrear.vue)
	createLabel("Cargo / Especialidad", 40, 272, 420, 16, app.hFontNormal, 0)
	app.hTxtCargo = createEdit(ID_TXT_CARGO, 40, 290, 420, 26, "Ej. Especialista en Soporte Técnico")

	// 5. CAMPO: CELULAR (Opcional, 9 dígitos)
	createLabel("Celular", 40, 324, 420, 16, app.hFontNormal, 0)
	app.hTxtCelular = createEdit(ID_TXT_CELULAR, 40, 342, 420, 26, "Ej. 987 654 321")

	// 6. CAMPO: CORREO ELECTRÓNICO (Opcional)
	createLabel("Correo electrónico", 40, 376, 420, 16, app.hFontNormal, 0)
	app.hTxtCorreo = createEdit(ID_TXT_CORREO, 40, 394, 420, 26, "Ej. usuario@ugelhuanuco.gob.pe")

	// 7. ESTADO Y BARRA DE PROGRESO
	app.hLblStatus = createLabel("Complete sus datos y haga clic en 'Registrar y Enviar Equipo'.", 40, 428, 420, 22, app.hFontSmall, ID_LBL_STATUS)

	hProg, _, _ := pCreateWindowExW.Call(
		0,
		uintptr(unsafe.Pointer(syscall.StringToUTF16Ptr("msctls_progress32"))),
		0,
		WS_CHILD,
		uintptr(40), uintptr(454), uintptr(420), uintptr(10),
		hwnd, uintptr(ID_PROGRESS), hInstance, 0,
	)
	pSendMessageW.Call(hProg, PBM_SETRANGE32, 0, 100)
	app.hProgress = hProg

	// 8. BOTONES DE ACCIÓN (Idénticos al pie de página de ModaCrear.vue de Inventario)
	hBtnReg, _, _ := pCreateWindowExW.Call(
		0,
		uintptr(unsafe.Pointer(syscall.StringToUTF16Ptr("BUTTON"))),
		uintptr(unsafe.Pointer(syscall.StringToUTF16Ptr("Registrar y Enviar Equipo"))),
		WS_CHILD|WS_VISIBLE|WS_TABSTOP|BS_OWNERDRAW,
		uintptr(40), uintptr(486), uintptr(295), uintptr(38),
		hwnd, uintptr(ID_BTN_REGISTRAR), hInstance, 0,
	)
	app.hBtnRegistrar = hBtnReg

	hBtnCan, _, _ := pCreateWindowExW.Call(
		0,
		uintptr(unsafe.Pointer(syscall.StringToUTF16Ptr("BUTTON"))),
		uintptr(unsafe.Pointer(syscall.StringToUTF16Ptr("Cerrar"))),
		WS_CHILD|WS_VISIBLE|WS_TABSTOP|BS_OWNERDRAW,
		uintptr(345), uintptr(486), uintptr(115), uintptr(38),
		hwnd, uintptr(ID_BTN_CANCELAR), hInstance, 0,
	)
	app.hBtnCancelar = hBtnCan

	// Mostrar ventana inmediatamente
	logMsg("[INIT] Paso 8: ShowWindow y UpdateWindow...")
	pShowWindow.Call(hwnd, SW_SHOW)
	pUpdateWindow.Call(hwnd)
	pSetFocus.Call(app.hTxtNombre)
	logMsg("[UI] Ventana principal moderna visible y lista para interactuar.")

	if autoTest {
		logMsg("[AUTO-TEST] Iniciando rutina de prueba funcional en vivo...")
		go func() {
			time.Sleep(1 * time.Second)
			logMsg("[AUTO-TEST] Completando campo 'Nombre completo'...")
			setWindowText(app.hTxtNombre, "Carlos Perez Lopez")
			logMsg("[AUTO-TEST] Seleccionando 'Oficina' (Índice 1)...")
			setComboCurSel(app.hCbOficina, 1)
			logMsg("[AUTO-TEST] Completando 'Cargo / Especialidad'...")
			setWindowText(app.hTxtCargo, "Especialista en Soporte")
			testCelular := fmt.Sprintf("9%08d", (time.Now().Unix()%90000000)+10000000)
			logMsg("[AUTO-TEST] Completando 'Celular' dinámico: %s", testCelular)
			setWindowText(app.hTxtCelular, testCelular)
			testEmail := fmt.Sprintf("carlos.test.%d@ugelhuanuco.gob.pe", time.Now().Unix())
			logMsg("[AUTO-TEST] Completando 'Correo electrónico' dinámico: %s", testEmail)
			setWindowText(app.hTxtCorreo, testEmail)
			time.Sleep(500 * time.Millisecond)
			logMsg("[AUTO-TEST] Simulando clic en botón 'Registrar y Enviar Equipo'...")
			pPostMessageW.Call(hwnd, WM_COMMAND, uintptr(ID_BTN_REGISTRAR), app.hBtnRegistrar)
		}()
	}

	// Bucle de mensajes activo con soporte para navegación por Tab (IsDialogMessage)
	var msg MSG
	for {
		ret, _, _ := pGetMessageW.Call(
			uintptr(unsafe.Pointer(&msg)),
			0, 0, 0,
		)
		if int32(ret) <= 0 {
			break
		}

		// Delegar a IsDialogMessageW EXCLUSIVAMENTE para teclas de navegación en controles (Tab, Flechas, Enter)
		isDlg := uintptr(0)
		if msg.Message >= 0x0100 && msg.Message <= 0x0109 {
			isDlg, _, _ = pIsDialogMessageW.Call(hwnd, uintptr(unsafe.Pointer(&msg)))
		}

		if isDlg == 0 {
			pTranslateMessage.Call(uintptr(unsafe.Pointer(&msg)))
			pDispatchMessageW.Call(uintptr(unsafe.Pointer(&msg)))
		}
	}

	// Limpieza de recursos GDI y GDI+
	pDeleteObject.Call(hWhiteBrush)
	pDeleteObject.Call(hBadgeBrush)
	pDeleteObject.Call(hBadgePen)
	pDeleteObject.Call(hGuindaBrush)
	pDeleteObject.Call(hAzulBrush)
	pDeleteObject.Call(hDividerBrush)

	pDeleteObject.Call(app.hFontHeaderTitle)
	pDeleteObject.Call(app.hFontHeaderSub)
	pDeleteObject.Call(app.hFontSection)
	pDeleteObject.Call(app.hFontBold)
	pDeleteObject.Call(app.hFontNormal)
	pDeleteObject.Call(app.hFontSmall)
	pDeleteObject.Call(app.hFontBadge)

	if imgMgr != nil {
		imgMgr.Release()
	}

	return nil
}

// -----------------------------------------------------------------------------
// VENTANA DE CONFIRMACIÓN MODERNA INSTITUCIONAL (INVENTARIO UGEL)
// -----------------------------------------------------------------------------

type SuccessDialogData struct {
	hDlg        uintptr
	hBtnAceptar uintptr
	responsable string
	oficina     string
	estado      string
	done        bool

	hWhiteBrush  uintptr
	hGreenBrush  uintptr
	hGreenPen    uintptr
	hCardBrush   uintptr
	hCardPen     uintptr
	hAmberBrush  uintptr
	hAmberPen    uintptr
	hAzulBrush   uintptr
	hGuindaBrush uintptr

	hFontCheck   uintptr
	hFontTitle   uintptr
	hFontLabel   uintptr
	hFontValBold uintptr
	hFontValNorm uintptr
	hFontBadge   uintptr
	hFontBtn     uintptr
}

var (
	currentDlgData     *SuccessDialogData
	dlgClassRegistered bool
	dlgClassMu         sync.Mutex
)

func registerDialogClass(hInstance uintptr) {
	dlgClassMu.Lock()
	defer dlgClassMu.Unlock()
	if dlgClassRegistered {
		return
	}

	hCursor, _, _ := pLoadCursorW.Call(0, uintptr(IDC_ARROW))
	hWhiteBrush, _, _ := pCreateSolidBrush.Call(rgb(255, 255, 255))
	dlgClass := syscall.StringToUTF16Ptr("UGEL_Confirmation_Modal_Class")

	var hClassIcon, hClassIconSm uintptr
	if currentApp != nil && currentApp.imageMgr != nil {
		hClassIcon = currentApp.imageMgr.hTaskbarIcon
		hClassIconSm = currentApp.imageMgr.hTransparentIcon
	}

	wc := WNDCLASSEXW{
		CbSize:        uint32(unsafe.Sizeof(WNDCLASSEXW{})),
		Style:         0x0003, // CS_HREDRAW | CS_VREDRAW
		LpfnWndProc:   syscall.NewCallback(dialogWndProc),
		HInstance:     hInstance,
		HIcon:         hClassIcon,
		HCursor:       hCursor,
		HbrBackground: hWhiteBrush,
		LpszClassName: dlgClass,
		HIconSm:       hClassIconSm,
	}
	pRegisterClassExW.Call(uintptr(unsafe.Pointer(&wc)))
	dlgClassRegistered = true
}

func dialogWndProc(hwnd uintptr, msg uint32, wParam, lParam uintptr) uintptr {
	if currentDlgData == nil {
		ret, _, _ := pDefWindowProcW.Call(hwnd, uintptr(msg), wParam, lParam)
		return ret
	}

	switch msg {
	case WM_PAINT:
		var ps PAINTSTRUCT
		hdc, _, _ := pBeginPaint.Call(hwnd, uintptr(unsafe.Pointer(&ps)))
		if hdc != 0 {
			paintConfirmationDialog(hdc, currentDlgData)
			pEndPaint.Call(hwnd, uintptr(unsafe.Pointer(&ps)))
			return 0
		}

	case WM_DRAWITEM:
		dis := (*DRAWITEMSTRUCT)(unsafe.Pointer(lParam))
		if dis.CtlID == ID_BTN_ACEPTAR {
			drawAceptarButton(dis, currentDlgData)
			return 1
		}

	case WM_COMMAND:
		ctrlID := uint16(wParam & 0xFFFF)
		notifyCode := uint16((wParam >> 16) & 0xFFFF)
		if notifyCode == BN_CLICKED && ctrlID == ID_BTN_ACEPTAR {
			pDestroyWindow.Call(hwnd)
			return 0
		}

	case WM_CLOSE:
		pDestroyWindow.Call(hwnd)
		return 0

	case WM_DESTROY:
		currentDlgData.done = true
		return 0
	}

	ret, _, _ := pDefWindowProcW.Call(hwnd, uintptr(msg), wParam, lParam)
	return ret
}

func drawAceptarButton(dis *DRAWITEMSTRUCT, d *SuccessDialogData) {
	hdc := dis.Hdc
	rc := dis.RcItem

	isPressed := (dis.ItemState & ODS_SELECTED) != 0

	var hBrush, hPen uintptr
	if isPressed {
		hBrush = d.hGuindaBrush
		hPen = d.hGuindaBrush
	} else {
		hBrush = d.hAzulBrush
		hPen = d.hAzulBrush
	}

	hOldBrush, _, _ := pSelectObject.Call(hdc, hBrush)
	hOldPen, _, _ := pSelectObject.Call(hdc, hPen)

	// Esquinas redondeadas radio 8px (rounded-lg de Tailwind)
	pRoundRect.Call(hdc, uintptr(rc.Left), uintptr(rc.Top), uintptr(rc.Right), uintptr(rc.Bottom), 8, 8)

	pSelectObject.Call(hdc, hOldBrush)
	pSelectObject.Call(hdc, hOldPen)

	// Dibujar texto blanco centrado con tipografía institucional en negrita
	pSetBkMode.Call(hdc, 1) // TRANSPARENT
	pSetTextColor.Call(hdc, rgb(255, 255, 255))
	hOldFont, _, _ := pSelectObject.Call(hdc, d.hFontBtn)

	text := syscall.StringToUTF16Ptr("Aceptar")
	pDrawTextW.Call(
		hdc,
		uintptr(unsafe.Pointer(text)),
		^uintptr(0),
		uintptr(unsafe.Pointer(&rc)),
		0x00000001|0x00000004|0x00000020, // DT_CENTER | DT_VCENTER | DT_SINGLELINE
	)
	pSelectObject.Call(hdc, hOldFont)
}

func paintConfirmationDialog(hdc uintptr, d *SuccessDialogData) {
	if d == nil {
		return
	}

	// 1. Fondo blanco puro (#FFFFFF) idéntico al sistema de inventario
	clientRc := RECT{Left: 0, Top: 0, Right: 440, Bottom: 310}
	pFillRect.Call(hdc, uintptr(unsafe.Pointer(&clientRc)), d.hWhiteBrush)

	// 2. Icono circular de confirmación exitosa (Emerald 100 con borde Emerald 300)
	circleLeft := int32(192)
	circleTop := int32(16)
	circleRight := int32(232)
	circleBottom := int32(56)

	hOldBrush, _, _ := pSelectObject.Call(hdc, d.hGreenBrush)
	hOldPen, _, _ := pSelectObject.Call(hdc, d.hGreenPen)
	pEllipse.Call(hdc, uintptr(circleLeft), uintptr(circleTop), uintptr(circleRight), uintptr(circleBottom))

	// Checkmark ✓ institucional en Emerald 600
	pSetBkMode.Call(hdc, 1) // TRANSPARENT
	pSetTextColor.Call(hdc, rgb(22, 163, 74)) // Emerald 600 #16A34A
	hOldFont, _, _ := pSelectObject.Call(hdc, d.hFontCheck)
	checkText := syscall.StringToUTF16Ptr("✓")
	rcCheck := RECT{Left: circleLeft, Top: circleTop, Right: circleRight, Bottom: circleBottom}
	pDrawTextW.Call(hdc, uintptr(unsafe.Pointer(checkText)), ^uintptr(0), uintptr(unsafe.Pointer(&rcCheck)), 0x00000001|0x00000004|0x00000020)

	// 3. Título ¡Registro completado! (Segoe UI 14pt Bold, Gray 900)
	pSelectObject.Call(hdc, d.hFontTitle)
	pSetTextColor.Call(hdc, rgb(17, 24, 39)) // Gray 900 #111827
	titleText := syscall.StringToUTF16Ptr("¡Registro completado!")
	rcTitle := RECT{Left: 20, Top: 64, Right: 404, Bottom: 88}
	pDrawTextW.Call(hdc, uintptr(unsafe.Pointer(titleText)), ^uintptr(0), uintptr(unsafe.Pointer(&rcTitle)), 0x00000001|0x00000004|0x00000020)

	// 4. Tarjeta contenedora de datos (Slate 50, borde Gray 200, radio 8px)
	cardLeft := int32(24)
	cardTop := int32(96)
	cardRight := int32(400)
	cardBottom := int32(200)

	pSelectObject.Call(hdc, d.hCardBrush)
	pSelectObject.Call(hdc, d.hCardPen)
	pRoundRect.Call(hdc, uintptr(cardLeft), uintptr(cardTop), uintptr(cardRight), uintptr(cardBottom), 8, 8)

	// Fila 1: Responsable
	pSelectObject.Call(hdc, d.hFontLabel)
	pSetTextColor.Call(hdc, rgb(100, 116, 139)) // Slate 500
	lblResp := syscall.StringToUTF16Ptr("Responsable:")
	rcLblResp := RECT{Left: 40, Top: 106, Right: 132, Bottom: 126}
	pDrawTextW.Call(hdc, uintptr(unsafe.Pointer(lblResp)), ^uintptr(0), uintptr(unsafe.Pointer(&rcLblResp)), 0x00000004|0x00000020)

	pSelectObject.Call(hdc, d.hFontValBold)
	pSetTextColor.Call(hdc, rgb(17, 24, 39)) // Gray 900
	valResp := syscall.StringToUTF16Ptr(d.responsable)
	rcValResp := RECT{Left: 134, Top: 106, Right: 388, Bottom: 126}
	pDrawTextW.Call(hdc, uintptr(unsafe.Pointer(valResp)), ^uintptr(0), uintptr(unsafe.Pointer(&rcValResp)), 0x00000004|0x00000020|0x00008000)

	// Fila 2: Oficina
	pSelectObject.Call(hdc, d.hFontLabel)
	pSetTextColor.Call(hdc, rgb(100, 116, 139)) // Slate 500
	lblOfi := syscall.StringToUTF16Ptr("Oficina:")
	rcLblOfi := RECT{Left: 40, Top: 136, Right: 132, Bottom: 156}
	pDrawTextW.Call(hdc, uintptr(unsafe.Pointer(lblOfi)), ^uintptr(0), uintptr(unsafe.Pointer(&rcLblOfi)), 0x00000004|0x00000020)

	pSelectObject.Call(hdc, d.hFontValNorm)
	pSetTextColor.Call(hdc, rgb(31, 41, 55)) // Gray 800
	valOfi := syscall.StringToUTF16Ptr(d.oficina)
	rcValOfi := RECT{Left: 134, Top: 136, Right: 388, Bottom: 156}
	pDrawTextW.Call(hdc, uintptr(unsafe.Pointer(valOfi)), ^uintptr(0), uintptr(unsafe.Pointer(&rcValOfi)), 0x00000004|0x00000020|0x00008000)

	// Fila 3: Estado
	pSelectObject.Call(hdc, d.hFontLabel)
	pSetTextColor.Call(hdc, rgb(100, 116, 139)) // Slate 500
	lblEst := syscall.StringToUTF16Ptr("Estado:")
	rcLblEst := RECT{Left: 40, Top: 167, Right: 132, Bottom: 189}
	pDrawTextW.Call(hdc, uintptr(unsafe.Pointer(lblEst)), ^uintptr(0), uintptr(unsafe.Pointer(&rcLblEst)), 0x00000004|0x00000020)

	// Badge Pill para Estado: "Revisión" (Amber 100, borde Amber 300, texto Amber 900)
	pillLeft := int32(134)
	pillTop := int32(167)
	pillRight := int32(222)
	pillBottom := int32(191)

	pSelectObject.Call(hdc, d.hAmberBrush)
	pSelectObject.Call(hdc, d.hAmberPen)
	pRoundRect.Call(hdc, uintptr(pillLeft), uintptr(pillTop), uintptr(pillRight), uintptr(pillBottom), 12, 12)

	pSelectObject.Call(hdc, d.hFontBadge)
	pSetTextColor.Call(hdc, rgb(120, 53, 15)) // Amber 900 #78350F
	badgeText := syscall.StringToUTF16Ptr(d.estado)
	rcBadge := RECT{Left: pillLeft, Top: pillTop, Right: pillRight, Bottom: pillBottom}
	pDrawTextW.Call(hdc, uintptr(unsafe.Pointer(badgeText)), ^uintptr(0), uintptr(unsafe.Pointer(&rcBadge)), 0x00000001|0x00000004|0x00000020)

	pSelectObject.Call(hdc, hOldBrush)
	pSelectObject.Call(hdc, hOldPen)
	pSelectObject.Call(hdc, hOldFont)
}

func showSuccessDialog(hParent uintptr, responsable, oficina, estado string) {
	hInstance, _, _ := pGetModuleHandleW.Call(0)
	registerDialogClass(hInstance)

	winWidth := 440
	winHeight := 305

	var rcParent RECT
	if hParent != 0 {
		pGetWindowRect.Call(hParent, uintptr(unsafe.Pointer(&rcParent)))
	}
	parentW := int(rcParent.Right - rcParent.Left)
	parentH := int(rcParent.Bottom - rcParent.Top)
	var posX, posY int
	if parentW > 0 && parentH > 0 {
		posX = int(rcParent.Left) + (parentW-winWidth)/2
		posY = int(rcParent.Top) + (parentH-winHeight)/2
	} else {
		screenWidth, _, _ := pGetSystemMetrics.Call(0)
		screenHeight, _, _ := pGetSystemMetrics.Call(1)
		posX = (int(screenWidth) - winWidth) / 2
		posY = (int(screenHeight) - winHeight) / 2
	}

	screenWidth, _, _ := pGetSystemMetrics.Call(0)
	screenHeight, _, _ := pGetSystemMetrics.Call(1)
	if posX < 0 || posX+winWidth > int(screenWidth) {
		posX = (int(screenWidth) - winWidth) / 2
	}
	if posY < 0 || posY+winHeight > int(screenHeight) {
		posY = (int(screenHeight) - winHeight) / 2
	}

	// Título vacío en la barra superior para eliminar texto redundante
	dlgTitle := syscall.StringToUTF16Ptr("")
	dlgClass := syscall.StringToUTF16Ptr("UGEL_Confirmation_Modal_Class")

	hDlg, _, _ := pCreateWindowExW.Call(
		0x00000001, // WS_EX_DLGMODALFRAME (sin icono en barra de título)
		uintptr(unsafe.Pointer(dlgClass)),
		uintptr(unsafe.Pointer(dlgTitle)),
		WS_POPUP|WS_CAPTION|WS_SYSMENU,
		uintptr(posX),
		uintptr(posY),
		uintptr(winWidth),
		uintptr(winHeight),
		0, // Ventana independiente al haberse cerrado el formulario principal
		0,
		hInstance,
		0,
	)

	if hDlg == 0 {
		logMsg("[ERROR] No se pudo crear la ventana de confirmación")
		return
	}

	// Paleta de colores idéntica a modales de Inventario UGEL
	hWhiteBrush, _, _ := pCreateSolidBrush.Call(rgb(255, 255, 255))
	hGreenBrush, _, _ := pCreateSolidBrush.Call(rgb(220, 252, 231))  // Emerald 100 #DCFCE7
	hGreenPen, _, _ := pCreatePen.Call(0, 1, rgb(134, 239, 172))    // Emerald 300 #86EFAC
	hCardBrush, _, _ := pCreateSolidBrush.Call(rgb(248, 250, 252))  // Slate 50 #F8FAFC
	hCardPen, _, _ := pCreatePen.Call(0, 1, rgb(229, 231, 235))     // Gray 200 #E5E7EB
	hAmberBrush, _, _ := pCreateSolidBrush.Call(rgb(254, 243, 199)) // Amber 100 #FEF3C7
	hAmberPen, _, _ := pCreatePen.Call(0, 1, rgb(252, 211, 77))     // Amber 300 #FCD34D
	hAzulBrush, _, _ := pCreateSolidBrush.Call(rgb(30, 58, 138))    // Azul UGEL #1E3A8A
	hGuindaBrush, _, _ := pCreateSolidBrush.Call(rgb(125, 10, 10))  // Guinda UGEL #7D0A0A

	hFontCheck := createFont("Segoe UI", 18, true)
	hFontTitle := createFont("Segoe UI", 14, true)
	hFontLabel := createFont("Segoe UI", 10, true)
	hFontValBold := createFont("Segoe UI", 11, true)
	hFontValNorm := createFont("Segoe UI", 11, false)
	hFontBadge := createFont("Segoe UI", 10, true)
	hFontBtn := createFont("Segoe UI", 11, true)

	d := &SuccessDialogData{
		hDlg:         hDlg,
		responsable:  responsable,
		oficina:      oficina,
		estado:       estado,
		hWhiteBrush:  hWhiteBrush,
		hGreenBrush:  hGreenBrush,
		hGreenPen:    hGreenPen,
		hCardBrush:   hCardBrush,
		hCardPen:     hCardPen,
		hAmberBrush:  hAmberBrush,
		hAmberPen:    hAmberPen,
		hAzulBrush:   hAzulBrush,
		hGuindaBrush: hGuindaBrush,
		hFontCheck:   hFontCheck,
		hFontTitle:   hFontTitle,
		hFontLabel:   hFontLabel,
		hFontValBold: hFontValBold,
		hFontValNorm: hFontValNorm,
		hFontBadge:   hFontBadge,
		hFontBtn:     hFontBtn,
	}
	currentDlgData = d

	defer func() {
		pDeleteObject.Call(hWhiteBrush)
		pDeleteObject.Call(hGreenBrush)
		pDeleteObject.Call(hGreenPen)
		pDeleteObject.Call(hCardBrush)
		pDeleteObject.Call(hCardPen)
		pDeleteObject.Call(hAmberBrush)
		pDeleteObject.Call(hAmberPen)
		pDeleteObject.Call(hAzulBrush)
		pDeleteObject.Call(hGuindaBrush)
		pDeleteObject.Call(hFontCheck)
		pDeleteObject.Call(hFontTitle)
		pDeleteObject.Call(hFontLabel)
		pDeleteObject.Call(hFontValBold)
		pDeleteObject.Call(hFontValNorm)
		pDeleteObject.Call(hFontBadge)
		pDeleteObject.Call(hFontBtn)
		currentDlgData = nil
	}()

	// Botón Aceptar centrado en la parte inferior
	hBtnAceptar, _, _ := pCreateWindowExW.Call(
		0,
		uintptr(unsafe.Pointer(syscall.StringToUTF16Ptr("BUTTON"))),
		uintptr(unsafe.Pointer(syscall.StringToUTF16Ptr("Aceptar"))),
		WS_CHILD|WS_VISIBLE|WS_TABSTOP|BS_OWNERDRAW,
		uintptr(150),
		uintptr(216),
		uintptr(140),
		uintptr(36),
		hDlg,
		uintptr(ID_BTN_ACEPTAR),
		hInstance,
		0,
	)
	d.hBtnAceptar = hBtnAceptar

	// Mostrar ventana de confirmación en primer plano
	pShowWindow.Call(hDlg, SW_SHOW)
	pUpdateWindow.Call(hDlg)
	pSetForegroundWindow.Call(hDlg)
	pSetFocus.Call(hBtnAceptar)

	// Bucle modal de mensajes
	var msg MSG
	for !d.done {
		ret, _, _ := pGetMessageW.Call(uintptr(unsafe.Pointer(&msg)), 0, 0, 0)
		if int32(ret) <= 0 {
			break
		}

		// Soporte de teclado (ESC, ENTER, BARRA ESPACIADORA cierran el diálogo)
		if msg.Message == 0x0100 { // WM_KEYDOWN
			if msg.WParam == 0x1B || msg.WParam == 0x0D || msg.WParam == 0x20 {
				pDestroyWindow.Call(hDlg)
				break
			}
		}

		pTranslateMessage.Call(uintptr(unsafe.Pointer(&msg)))
		pDispatchMessageW.Call(uintptr(unsafe.Pointer(&msg)))
	}
}
