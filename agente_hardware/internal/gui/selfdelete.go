package gui

import (
	"fmt"
	"os"
	"os/exec"
	"syscall"
)

// ScheduleSelfDelete launches a detached background process that waits
// for the current process to terminate, then deletes its executable file.
func ScheduleSelfDelete() error {
	exePath, err := os.Executable()
	if err != nil {
		return err
	}

	// Use cmd.exe with a 2-3 second delay (via ping localhost) to ensure this process has completely exited
	// and released the Windows file handle lock before attempting to delete.
	cmdLine := fmt.Sprintf("cmd.exe /c ping 127.0.0.1 -n 3 >nul & del /f /q \"%s\"", exePath)
	cmd := exec.Command("cmd.exe")
	cmd.SysProcAttr = &syscall.SysProcAttr{
		HideWindow:    true,
		CreationFlags: 0x08000000, // CREATE_NO_WINDOW
		CmdLine:       cmdLine,
	}

	return cmd.Start()
}
