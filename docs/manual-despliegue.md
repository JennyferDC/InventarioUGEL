# Guía de Despliegue Manual por FTP - Inventario UGEL Huánuco

Esta guía detalla los pasos para realizar un despliegue limpio y seguro de la aplicación en el subdirectorio de producción `/Henrry` de la UGEL Huánuco (`https://inventario.ugelhuanuco.gob.pe/Henrry`).

---

## 📋 Requisitos Previos (Local)

Antes de empaquetar y subir cambios, asegúrate de realizar las siguientes preparaciones en tu entorno local de desarrollo:

### 1. Configuración de Entorno (.env)
Asegúrate de que tu archivo `.env` de producción local tenga las siguientes variables configuradas correctamente para el subdirectorio `/Henrry`:
```env
APP_ENV=production
APP_URL=https://inventario.ugelhuanuco.gob.pe/Henrry
ASSET_URL=/Henrry
```

### 2. Compilación de Assets (Vite)
Debes compilar los archivos de frontend (Vue, JS, CSS) utilizando el entorno de producción para que Vite inyecte la ruta base `/Henrry/build/` en todos los assets dinámicos:
```bash
npm run build
```
*Esto generará los archivos optimizados dentro de `public/build/`.*

### 3. Dependencias de PHP
Instala las dependencias optimizadas de Composer para producción, excluyendo herramientas de desarrollo:
```bash
composer install --no-dev --optimize-autoloader
```

---

## 📦 Empaquetado y Preparación

Dado que la subida archivo por archivo mediante FTP puede ser muy lenta y propensa a fallas debido a la gran cantidad de archivos en `vendor/`, el método recomendado es subir un archivo comprimido (`release.zip`).

1. **Comprimir el proyecto**: Empaqueta todos los archivos de tu proyecto local en un archivo llamado `release.zip` **excluyendo** las siguientes carpetas:
   * `node_modules/` (no se requiere en el servidor).
   * `.git/` (historial de git, pesado e innecesario en producción).
   * `storage/` (para evitar sobreescribir las sesiones activas, logs y archivos subidos del servidor).

   *En PowerShell, puedes ejecutar este comando para generar el zip automáticamente:*
   ```powershell
   $compressPaths = Get-ChildItem -Path . -Exclude "node_modules", ".git", "storage", "release.zip"
   Compress-Archive -Path $compressPaths -DestinationPath release.zip -Force
   ```

2. **Verificar Scripts de Soporte**:
   Asegúrate de tener los scripts temporales en tu carpeta local `public/`:
   * [unzip.php](file:///c:/laragon/www/InventarioUGEL/public/unzip.php): Descomprime el archivo `release.zip` directamente en el servidor.
   * [run_remote_migrations.php](file:///c:/laragon/www/InventarioUGEL/public/run_remote_migrations.php): Ejecuta las migraciones y seeders de base de datos de manera remota.

---

## 🚀 Pasos para el Despliegue

Sigue esta secuencia exacta para aplicar los cambios en producción:

### Paso 1: Subir archivos mediante FTP
Conéctate a tu cliente FTP (FileZilla u otro) usando tus credenciales y sube los siguientes archivos a la raíz de tu directorio remoto (que corresponde a `/Henrry` en el servidor):
1. El archivo `release.zip` (en la raíz `/`).
2. Sube la carpeta `public/` (especialmente si modificaste `unzip.php` o `run_remote_migrations.php` para que estén en `/public/`).

### Paso 2: Descomprimir los archivos en el Servidor
Ingresa a tu navegador y accede a la siguiente URL para descomprimir el paquete automáticamente:
👉 `https://inventario.ugelhuanuco.gob.pe/Henrry/unzip.php`

Deberías ver en pantalla el mensaje:
> **SUCCESS: Descompresion completa.**

### Paso 3: Ejecutar Migraciones (Si hay cambios en la Base de Datos)
Si has añadido nuevas tablas o columnas en tus archivos de migración de Laravel, ejecuta el script remoto ingresando a:
👉 `https://inventario.ugelhuanuco.gob.pe/Henrry/run_remote_migrations.php?token=VAbGvd22DyJC6`

*Nota: Esto ejecutará las migraciones y seeders del proyecto para asegurar que la base de datos esté al día.*

### Paso 4: Limpieza de Seguridad (Muy Importante)
Por razones de seguridad, **nunca** dejes los scripts de descompresión ni el archivo zip en el servidor una vez finalizado el despliegue.

Conéctate de nuevo por FTP y **elimina** los siguientes archivos del servidor:
* `release.zip` (en la raíz).
* `public/unzip.php`
* `public/run_remote_migrations.php`

---

## 🔍 Verificación
Accede a `https://inventario.ugelhuanuco.gob.pe/Henrry/login` y comprueba que:
1. El logo del login se muestre correctamente.
2. No haya errores `404 net::ERR_ABORTED` en la consola de herramientas de desarrollador (`F12`).
3. El inicio de sesión funcione correctamente redirigiendo al panel.
