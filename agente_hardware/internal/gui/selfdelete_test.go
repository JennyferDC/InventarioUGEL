package gui

import (
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"syscall"
	"testing"
	"time"
)

func TestSelfDeleteLogic(t *testing.T) {
	tmpDir := t.TempDir()
	dummyFile := filepath.Join(tmpDir, "dummy_agent.exe")

	if err := os.WriteFile(dummyFile, []byte("dummy binary content"), 0755); err != nil {
		t.Fatalf("Error creating dummy file: %v", err)
	}

	cmdLine := fmt.Sprintf("cmd.exe /c ping 127.0.0.1 -n 2 >nul & del /f /q \"%s\"", dummyFile)
	cmd := exec.Command("cmd.exe")
	cmd.SysProcAttr = &syscall.SysProcAttr{
		HideWindow:    true,
		CreationFlags: 0x08000000,
		CmdLine:       cmdLine,
	}

	if err := cmd.Start(); err != nil {
		t.Fatalf("Failed to start detached delete process: %v", err)
	}

	// Wait for background process to finish deleting
	time.Sleep(3 * time.Second)

	if _, err := os.Stat(dummyFile); !os.IsNotExist(err) {
		t.Errorf("Expected file to be deleted, but it still exists (err=%v)", err)
	}
}
