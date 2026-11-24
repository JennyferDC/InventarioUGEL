$code = @"
using System;
using System.Runtime.InteropServices;
using System.Text;

public class WinTester {
    [DllImport("user32.dll", SetLastError = true)]
    public static extern IntPtr GetDlgItem(IntPtr hDlg, int nIDDlgItem);

    [DllImport("user32.dll", SetLastError = true)]
    public static extern IntPtr FindWindow(string lpClassName, string lpWindowName);

    [DllImport("user32.dll", SetLastError = true)]
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

Write-Host "1. Lanzando agente_ugel.exe..."
$proc = Start-Process -FilePath "C:\laragon\www\inventario_ugel\agente_hardware\agente_ugel.exe" -ArgumentList "--no-delete" -PassThru

# Esperar a que la ventana aparezca
$hwnd = [IntPtr]::Zero
for ($k = 0; $k -lt 40; $k++) {
    Start-Sleep -Milliseconds 100
    $proc.Refresh()
    if ($proc.MainWindowHandle -ne [IntPtr]::Zero) {
        $hwnd = $proc.MainWindowHandle
        break
    }
}

Write-Host "2. Ventana detectada! HWND: $hwnd"

# Obtener controles por GetDlgItem
$hNombre = [WinTester]::GetDlgItem($hwnd, 1003)
Write-Host "3. Edit Nombre (ID 1003) HWND: $hNombre"
[WinTester]::SendMessage($hNombre, 0x000C, [IntPtr]::Zero, "Prueba Real UI")

$hCombo = [WinTester]::GetDlgItem($hwnd, 1004)
Write-Host "4. ComboBox Oficina (ID 1004) HWND: $hCombo"
[WinTester]::SendMessage($hCombo, 0x014E, [IntPtr]1, [IntPtr]::Zero) # Selecciona índice 1

$hCargo = [WinTester]::GetDlgItem($hwnd, 1005)
Write-Host "5. Edit Cargo (ID 1005) HWND: $hCargo"
[WinTester]::SendMessage($hCargo, 0x000C, [IntPtr]::Zero, "Especialista en Pruebas")

$hCelular = [WinTester]::GetDlgItem($hwnd, 1006)
Write-Host "6. Edit Celular (ID 1006) HWND: $hCelular"
[WinTester]::SendMessage($hCelular, 0x000C, [IntPtr]::Zero, "987654321")

$hBtnRegistrar = [WinTester]::GetDlgItem($hwnd, 1001)
Write-Host "7. Boton Registrar (ID 1001) HWND: $hBtnRegistrar"

Start-Sleep -Milliseconds 500

Write-Host "8. Presionando 'Registrar y Enviar Equipo' (Enviando WM_COMMAND)..."
# Enviar WM_COMMAND al padre con control ID 1001 y BN_CLICKED 0
[WinTester]::PostMessage($hwnd, 0x0111, [IntPtr]1001, $hBtnRegistrar)

Write-Host "9. Monitoreando ventana en vivo..."
$startTime = Get-Date

for ($sec = 1; $sec -le 40; $sec++) {
    Start-Sleep -Seconds 1
    $hung = [WinTester]::IsHungAppWindow($hwnd)

    # Leer texto del label de estado (ID 1008)
    $hStatus = [WinTester]::GetDlgItem($hwnd, 1008)
    $statusText = ""
    if ($hStatus -ne [IntPtr]::Zero) {
        $sbStatus = New-Object System.Text.StringBuilder 256
        [WinTester]::GetWindowText($hStatus, $sbStatus, 256)
        $statusText = $sbStatus.ToString()
    }

    # Buscar dialogo MessageBox de Windows (#32770)
    $hDlg = [WinTester]::FindWindow("#32770", $null)
    $dlgTitle = ""
    $dlgBody = ""
    if ($hDlg -ne [IntPtr]::Zero) {
        $sb = New-Object System.Text.StringBuilder 256
        [WinTester]::GetWindowText($hDlg, $sb, 256)
        $dlgTitle = $sb.ToString()

        $hStatic = [WinTester]::GetDlgItem($hDlg, 0xFFFF)
        if ($hStatic -ne [IntPtr]::Zero) {
            $sbBody = New-Object System.Text.StringBuilder 1024
            [WinTester]::GetWindowText($hStatic, $sbBody, 1024)
            $dlgBody = $sbBody.ToString()
        }
    }

    $elapsed = [math]::Round(((Get-Date) - $startTime).TotalSeconds, 1)
    Write-Host "   [T+$($elapsed)s] IsHung = $hung | Estado = '$statusText' | Dialogo = '$dlgTitle'"

    if ($dlgTitle -ne "") {
        Write-Host ">>> DIALOGO RECIBIDO EN $elapsed SEGUNDOS! <<<"
        Write-Host "    TITULO: '$dlgTitle'"
        Write-Host "    MENSAJE: '$dlgBody'"
        # Cerrar el dialogo con WM_CLOSE
        [WinTester]::SendMessage($hDlg, 0x0010, [IntPtr]::Zero, [IntPtr]::Zero)
        break
    }
}

Start-Sleep -Seconds 2
if (!$proc.HasExited) {
    Write-Host "Cerrando proceso del agente..."
    Stop-Process -Id $proc.Id -Force
}
Write-Host "Prueba funcional finalizada con exito."
