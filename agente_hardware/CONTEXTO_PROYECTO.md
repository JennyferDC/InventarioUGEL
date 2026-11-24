# Resumen Técnico de Continuidad del Proyecto
## Agente BOT de Inventario UGEL Huánuco

**Fecha de emisión:** 18 de Noviembre de 2026  
**Ubicación del componente:** `C:\laragon\www\inventario_ugel\agente_hardware`  
**Sistema web de inventario:** `C:\laragon\www\inventario_ugel`  
**Ejecutable compilado:** `agente_hardware\agente_ugel.exe` (distribuido en `public\descargas\agente_ugel.exe`)

---

## 1. Estado Actual del Proyecto y Funcionalidades Implementadas

El agente BOT es un aplicativo de escritorio autónomo, ligero y portable desarrollado en lenguaje **Go**, diseñado para ser distribuido mediante memorias USB o descarga directa desde el sistema web institucional. Su función primordial es auditar exhaustivamente las estaciones de trabajo de la UGEL Huánuco y sincronizarlas de forma desatendida con el sistema central de inventario.

### Funcionalidades operativas:
1. **Auditoría automática de hardware:**
   - Detección precisa de características físicas mediante WMI y APIs nativas de Windows:
     - Procesador (Nombre, fabricante, núcleos, velocidad de reloj).
     - Memoria RAM (Capacidad total en GB, velocidad, cantidad y especificación de slots).
     - Almacenamiento (Discos físicos, tipo SSD/HDD, capacidad, número de serie).
     - Placa base (Fabricante, modelo y número de serie).
     - Tarjetas de red (Adaptadores Ethernet y Wi-Fi con direcciones MAC y direcciones IP asignadas).
     - Tarjeta gráfica / GPU y monitor conectado.
     - Nombre del equipo en red (Hostname) y usuario del sistema operativo activo.
2. **Auditoría automática de software y programas instalados:**
   - Escaneo directo del Registro de Windows en rutas de 32 bits y 64 bits (`HKLM` y `HKCU\Software\Microsoft\Windows\CurrentVersion\Uninstall`).
   - Normalización de nombres de programas, versiones, editor/desarrollador y fechas de instalación, descartando dependencias internas y actualizaciones del sistema.
3. **Formulario nativo de asignación de responsabilidad:**
   - Carga dinámica y asíncrona del catálogo de oficinas y áreas desde la API REST (`/api/v1/agente/oficinas`).
   - Registro de datos del servidor público responsable: Nombre completo, Oficina institucional, Cargo o especialidad, Celular de contacto y Correo electrónico.
   - Normalización de textos (formato título para nombres y cargos, saneamiento de celular a 9 dígitos para Perú y validación estricta de correo).
4. **Transmisión y registro en el Sistema Central:**
   - Ensamblado de payload unificado en formato JSON respetando el diccionario de campos del sistema de inventario.
   - Envío autenticado mediante Bearer Token a la ruta API `/api/v1/agente/registro`.
   - Registro automático en la base de datos con estado **BORRADOR**, permitiendo que el personal de Informática / Patrimonio valide y confirme formalmente el alta del equipo.
5. **Ciclo de vida y auto-eliminación:**
   - Opción `--auto-delete` (activada por defecto en producción): tras enviar exitosamente los datos y cerrarse el aplicativo, programa un script en segundo plano para auto-eliminarse de la carpeta de Descargas del usuario sin dejar rastros residuales.

---

## 2. Últimos Cambios Realizados en la Interfaz y Ventana de Confirmación

Se llevó a cabo una modernización visual completa del agente para homologar su identidad gráfica con el módulo **Inventario de Equipos** del sistema web Laravel/Vue:

1. **Paleta de colores y componentes institucionales:**
   - Fondo blanco puro (`#FFFFFF`) con bordes suaves.
   - Botón primario en **Azul Institucional UGEL** (`#1E3A8A`) con efecto de pulsado en **Guinda Institucional UGEL** (`#7D0A0A`) y texto blanco en negrita con esquinas redondeadas (radio 8px, equivalente a `rounded-lg` de Tailwind).
   - Franja de acento bicolor institucional (Guinda / Azul) en la cabecera.
   - Logotipo oficial de la UGEL Huánuco embebido directamente en el binario y renderizado con antialiasing bicúbico y canal alfa mediante GDI+.
2. **Depuración de textos redundantes y estilización de ventana:**
   - Se removió el texto duplicado *"UGEL Huánuco"* tanto del título de la ventana como del costado del logotipo, dejando únicamente `Registro de Equipos de Cómputo`.
   - Se eliminó el icono tosco por defecto en la esquina superior izquierda de la barra de título mediante el estilo extendido `WS_EX_DLGMODALFRAME` y un icono transparente de 16x16.
   - Se diseñó un icono minimalista de computadora con fondo azul institucional (`#1E3A8A`) generado en memoria DIB para la barra de tareas de Windows.
   - Se compactaron las dimensiones del formulario principal a 500x580 px con un ancho uniforme y equilibrado de 420 px para todos los controles.
3. **Rediseño completo de la Ventana de Confirmación:**
   - Se sustituyó la ventana emergente genérica de Windows (`MessageBoxW`) por una ventana modal personalizada con diseño web institucional (440x305 px):
     - **Isotipo de confirmación:** Círculo verde esmeralda (`#DCFCE7` con borde `#86EFAC`) con el símbolo de verificación institucional `✓` en tono esmeralda (`#16A34A`).
     - **Título principal:** `¡Registro completado!` en Segoe UI 14pt negrita.
     - **Tarjeta informativa:** Contenedor gris tenue (`#F8FAFC`, borde `#E5E7EB`, esquinas redondeadas) mostrando estrictamente:
       - `Responsable:` Nombre del responsable asignado.
       - `Oficina:` Oficina institucional correspondiente.
       - `Estado:` Badge tipo pastilla en tono ámbar (`#FEF3C7`, borde `#FCD34D`, texto `#78350F`) con el valor `Revisión`.
     - **Eliminación de datos técnicos:** Se retiraron conteos de programas, especificaciones de hardware y avisos técnicos auxiliares para mantener una interfaz limpia y ejecutiva.
     - **Botón `Aceptar`:** Centrado, con colores institucionales y soporte total para teclado (`Enter`, `Espacio`, `Escape`).
   - **Comportamiento post-envío:**
     - Al completarse el registro, la ventana del formulario se oculta de inmediato (`SW_HIDE`), quedando visible en pantalla **únicamente la ventana de confirmación**.
     - Se eliminó el título redundante de la barra superior de la ventana de confirmación.

---

## 3. Soluciones Aplicadas al Problema de Congelamiento (Freeze de UI)

### Diagnóstico de la causa raíz:
En versiones iniciales, al pulsar *"Registrar y Enviar Equipo"*, la interfaz se congelaba por varios segundos y Windows marcaba la ventana como *"No responde"*. Esto se debía a que las tareas pesadas y bloqueantes —extracción profunda de hardware por WMI (1 a 2 segundos), lectura de miles de claves de registro para programas (0.5 a 1 segundo) y la petición HTTP síncrona a la API REST (1 a 2 segundos)— se ejecutaban directamente dentro de la función manejadora de eventos en el mismo hilo del bucle de mensajes de Win32 (`wndProc`), impidiendo el procesamiento de `GetMessageW` / `DispatchMessageW`.

### Arquitectura de solución implementada:
1. **Desacoplamiento total con Goroutines:**
   - La función `onRegistrarClicked()` realiza la validación inmediata de los campos en el hilo de UI (< 1 ms).
   - Si los datos son válidos, bloquea los campos para evitar pulsaciones dobles, muestra una barra de progreso animada y delega toda la carga pesada a una **goroutine independiente** en segundo plano (`go func()`).
   - La goroutine cuenta con captura de excepciones (`defer recover()`) para evitar caídas silenciosas ante cualquier fallo de hardware o WMI.
2. **Mensajería asíncrona Win32 (`PostMessageW`):**
   - El hilo secundario nunca toca los handles (`HWND`) directamente de manera síncrona; en su lugar, se comunica con la interfaz mediante mensajes de usuario personalizados no bloqueantes:
     - `WM_APP_PROGRESS_UPDATE`: Actualiza el porcentaje de la barra de progreso (15%, 45%, 70%, 85%, 100%).
     - `WM_APP_STATUS_UPDATE`: Actualiza el texto de estado ("Paso 1/3: Recopilando hardware...", etc.).
     - `WM_APP_SHOW_WARNING`: Dispara alertas de validación devueltas por el servidor.
     - `WM_APP_SHOW_ERROR`: Notifica fallos de conectividad o errores críticos.
     - `WM_APP_SUCCESS`: Despierta el hilo principal para ocultar el formulario y desplegar la ventana de confirmación.
3. **Optimización del bucle de eventos:**
   - El hilo principal permanece fijado con `runtime.LockOSThread()`.
   - Se utiliza `IsDialogMessageW` de forma selectiva para teclas de navegación (`Tab`, flechas, `Enter`), permitiendo que la interfaz responda a 60 FPS sin importar la carga de trabajo en segundo plano.

---

## 4. Archivos Modificados y Rutas del Proyecto

```
C:\laragon\www\inventario_ugel\
│
├── public\descargas\
│   └── agente_ugel.exe                  # Copia de distribución web descargable
│
└── agente_hardware\
    ├── agente_ugel.exe                  # Binario compilado final para Windows x64
    ├── main.go                          # Punto de entrada y gestión de flags CLI
    ├── go.mod                           # Módulos y dependencias de Go
    ├── go.sum                           # Checksums de dependencias
    ├── agente_debug.log                 # Log de depuración en ejecución
    │
    ├── internal\
    │   ├── gui\
    │   │   ├── gui.go                   # Ventana principal, wndProc, goroutine asíncrona,
    │   │   │                            # estilos owner-draw y diálogo modal de confirmación
    │   │   ├── image.go                 # Inicialización GDI+, antialiasing bicúbico,
    │   │   │                            # generación de iconos en memoria DIB
    │   │   ├── selfdelete_windows.go    # Rutina de autodestrucción post-ejecución
    │   │   └── assets\
    │   │       └── logo.png             # Logotipo oficial UGEL Huánuco embebido
    │   │
    │   ├── client\
    │   │   └── api.go                   # Cliente HTTP REST, timeout y Bearer Token
    │   │
    │   ├── hardware\
    │   │   ├── collector.go             # Orquestador de recopilación técnica
    │   │   ├── cpu.go                   # Métricas de CPU vía WMI
    │   │   ├── ram.go                   # Métricas de memoria física
    │   │   ├── disk.go                  # Particiones y unidades de disco
    │   │   ├── motherboard.go           # Fabricante y serial de placa base
    │   │   ├── network.go               # Adaptadores de red, IP y MAC
    │   │   ├── os.go                    # Versión y arquitectura de Windows
    │   │   ├── gpu.go                   # Tarjetas gráficas
    │   │   ├── monitor.go               # Pantallas conectadas
    │   │   └── usb.go                   # Dispositivos USB detectados
    │   │
    │   ├── programs\
    │   │   ├── collector.go             # Explorador del Registro de Windows
    │   │   └── program.go               # Estructuras y filtros de software
    │   │
    │   ├── persona\
    │   │   ├── model.go                 # Modelo de datos del responsable
    │   │   ├── oficinas.go              # Consumo de oficinas desde la API
    │   │   ├── validator.go             # Validadores de celular y correo
    │   │   └── formatter.go             # Normalizador de nombres y cargos
    │   │
    │   └── processor\
    │       ├── mapper.go                # Mapeo a especificación de Laravel
    │       └── payload.go               # Construcción del payload JSON final
```

---

## 5. Tecnologías y Estructura Actual del Proyecto

| Componente | Tecnología / Librería | Propósito |
| :--- | :--- | :--- |
| **Lenguaje Core** | Go 1.22+ (`windows/amd64`) | Rendimiento nativo, portabilidad y ejecutable monolítico |
| **Interfaz Gráfica** | Win32 API pura (`user32.dll`, `gdi32.dll`, `comctl32.dll`) | GUI nativa de carga instantánea (< 50ms) sin frameworks pesados ni dependencias runtime |
| **Renderizado Gráfico** | GDI+ (`gdiplus.dll`) | Dibujo bicúbico con canal alfa para el logotipo oficial institucional |
| **Auditoría Hardware** | WMI (`github.com/StackExchange/wmi`, Syscalls nativas) | Extracción de hardware profundo del equipo |
| **Auditoría Software** | `golang.org/x/sys/windows/registry` | Lectura directa de claves de desinstalación de Windows |
| **Backend Receptor** | Laravel 11 (PHP 8.2+), MariaDB/MySQL | API REST receptora y lógica de inventario patrimonial |
| **Frontend Web** | Vue 3 + Inertia.js + Tailwind CSS | Módulo de inventario del cual se extrajo la identidad visual |
| **Autenticación API** | Middleware `VerifyAgentToken` | Validación de cabecera `Authorization: Bearer <token>` |

---

## 6. Estado de la Base de Datos, Cambios Pendientes y Consideraciones

### Estado actual de la base de datos tras la limpieza controlada:
Se depuraron los datos de prueba dejando **exactamente 1 registro de referencia** por módulo, garantizando integridad referencial y relaciones consistentes:
- **Módulo Personas:** 1 registro activo (`ID: 1`, Juan Pérez, Oficina Dirección).
- **Módulo Equipos:** 1 equipo operativo (`ID: 13`, `EQUPC13` / `PC-UGT-797`, PC asignada a Juan Pérez).
- **Módulo Programas:** 1 programa institucional (`ID: 1`, `PROINS1`, Sistema de Control de Asistencias, vinculado al equipo 13 en `equipo_programa`).
- **Tablas maestras intactas:** Áreas (6), Oficinas (18), Usuarios del sistema (2) y Cronogramas de mantenimiento (2) permanecen completamente funcionales.

### Consideraciones técnicas para despliegue y continuidad:
1. **Compilación para producción:**
   - Para compilar el agente sin consola negra de depuración:
     ```powershell
     cd C:\laragon\www\inventario_ugel\agente_hardware
     & "C:\Program Files\Go\bin\go.exe" build -ldflags="-H=windowsgui -s -w" -o agente_ugel.exe .
     Copy-Item -Path "agente_ugel.exe" -Destination "..\public\descargas\agente_ugel.exe" -Force
     ```
2. **Firmado digital (Code Signing):**
   - En entornos corporativos o PCs con Windows Defender SmartScreen estricto, se recomienda firmar el ejecutable con el certificado digital de la institución para evitar avisos de *"Editor desconocido"*.
3. **Privilegios de ejecución:**
   - La extracción de hardware y programas funciona correctamente con privilegios de usuario estándar de Windows; no requiere permisos elevados de Administrador a menos que las políticas de grupo locales restrinjan el acceso a WMI.
4. **Token de agente:**
   - El token de comunicación está definido en el archivo de entorno `.env` (`AGENTE_HARDWARE_TOKEN`) y en `config/services.php`. Ambos deben mantenerse sincronizados si se actualiza la clave en producción.
