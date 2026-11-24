package programs

import (
	"testing"
)

func TestCollectInstalledPrograms(t *testing.T) {
	progs, err := CollectInstalledPrograms()
	if err != nil {
		t.Fatalf("CollectInstalledPrograms falló: %v", err)
	}

	t.Logf("Se detectaron %d programas instalados.", len(progs))
	if len(progs) == 0 {
		t.Log("Aviso: No se encontraron programas en el registro o máquina virtual limpia.")
	}

	for i, p := range progs {
		if i < 10 {
			t.Logf("  [%d] %s (Versión: %s | Fabricante: %s)", i+1, p.Name, p.Version, p.Publisher)
		}
	}
}
