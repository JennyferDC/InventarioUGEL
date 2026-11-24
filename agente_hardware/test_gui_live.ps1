$code = @"
using System;
using System.Runtime.InteropServices;
using System.Text;

public class Win32 {
    [DllImport("user32.dll", SetLastError = true, CharSet = CharSet.Auto)]
    public static extern IntPtr FindWindow(string lpClassName, string lpWindowName);

    [DllImport("user32.dll", SetLastError = true, CharSet = CharSet.Auto)]
    public static extern IntPtr FindWindowEx(IntPtr hwndParent, IntPtr hwndChildAfter, string lpszClass, string lpszWindow);

    [DllImport("user32.dll", CharSet = CharSet.Auto)]
    public static extern IntPtr SendMessage(IntPtr hWnd, uint Msg, IntPtr wParam, string lParam);

    [DllImport("user32.dll")]
    public static extern IntPtr SendMessage(IntPtr hWnd, uint Msg, IntPtr wParam, IntPtr lParam);

    [DllImport("user32.dll")]
    public static extern bool PostMessage(IntPtr hWnd, uint Msg, IntPtr wParam, IntPtr lParam);

    [DllImport("user32.dll")]
    public static extern bool IsHungAppWindow(IntPtr hWnd);

    [DllImport("user32.dll", CharSet = CharSet.Auto)]
    public static extern bool GetWindowText(IntPtr hWnd, StringBuilder lpString, int nMaxCount);
}
"@

Add-Type -TypeDefinition $code -ErrorAction SilentlyContinue

Write-Host "Iniciando agente_ugel.exe..."
$proc = Start-Process -FilePath "C:\laragon\www\inventario_ugel\agente_hardware\agente_ugel.exe" -ArgumentList "--no-delete" -PassThru

$hwnd = [IntPtr]::Zero
for ($w = 0; $w -lt 20; $w++) {
    Start-Sleep -Milliseconds 250
    $hwnd = [Win32]::FindWindow("UGEL_Inventario_GUI_Class", $null)
    if ($hwnd -ne [IntPtr]::Zero) { break }
}

Write-Host "HWND Ventana principal:" $hwnd

if ($hwnd -eq [IntPtr]::Zero) {
    Write-Error "No se encontro la ventana del agente."
    Stop-Process -Id $proc.Id -Force
    exit 1
}

# WM_SETTEXT = 0x000C
# CB_SETCURSEL = 0x014E
# BM_CLICK = 0x00F5
# WM_COMMAND = 0x0111

# Buscar campos por clase EDIT
$hNombre = [Win32]::FindWindowEx($hwnd, [IntPtr]::Zero, "EDIT", $null)
Write-Host "HWND Edit Nombre:" $hNombre
[Win32]::SendMessage($hNombre, 0x000C, [IntPtr]::Zero, "Carlos Perez Lopez")

$hCombo = [Win32]::FindWindowEx($hwnd, [IntPtr]::Zero, "COMBOBOX", $null)
Write-Host "HWND Combobox Oficina:" $hCombo
[Win32]::SendMessage($hCombo, 0x014E, [IntPtr]1, [IntPtr]::Zero) # Selecciona index 1

# Buscar boton Registrar
$hBtnRegistrar = [Win32]::FindWindowEx($hwnd, [IntPtr]::Zero, "BUTTON", "Registrar y Enviar Equipo")
Write-Host "HWND Boton Registrar:" $hBtnRegistrar

Write-Host "Presionando 'Registrar y Enviar Equipo'..."
# Send WM_COMMAND con ID_BTN_REGISTRAR = 1001 y BN_CLICKED = 0
[Win32]::PostMessage($hwnd, 0x0111, [IntPtr]1001, $hBtnRegistrar)

Write-Host "Monitoreando ventana en vivo..."
for ($i = 1; $i -le 35; $i++) {
    Start-Sleep -Seconds 1
    $isHung = [Win32]::IsHungAppWindow($hwnd)

    # Buscar dialogo MessageBox
    $hMsgBox = [Win32]::FindWindow("#32770", $null)
    $msgTitle = ""
    if ($hMsgBox -ne [IntPtr]::Zero) {
        $sb = New-Object System.Text.StringBuilder 256
        [Win32]::GetWindowText($hMsgBox, $sb, 256)
        $msgTitle = $sb.ToString()
    }

    Write-Host "Segundo $i : IsHungAppWindow = $isHung | Dialogo = '$msgTitle'"
    if ($msgTitle -ne "") {
        Write-Host "=== RESULTADO DETECTADO: '$msgTitle' ==="
        # Cerrar el dialogo
        [Win32]::SendMessage($hMsgBox, 0x0010, [IntPtr]::Zero, [IntPtr]::Zero)
        break
    }
}

if (!$proc.HasExited) {
    Write-Host "Cerrando proceso..."
    Stop-Process -Id $proc.Id -Force
}
Write-Host "Prueba completada."
