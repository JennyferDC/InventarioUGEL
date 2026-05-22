# Acceso a Datos del Sistema por Servicios de IA

## 🛠️ 1. Servicio: Profesionalización de Observaciones Técnicas de Baja
Tiene acceso de lectura únicamente a la siguiente información del sistema:
- **Observación del operador**: Descripción manual o motivo informal de la baja ingresado por el técnico.

---

## 🧠 2. Servicio: Diagnóstico Técnico e Inteligencia de Consulta (Drawer de IA)
Tiene acceso de lectura a los siguientes datos y campos del equipo consultado:

### A. Datos Generales del Equipo
- **cod_informatica**: Código de informática del equipo.
- **categoria**: Categoría del equipo.
- **ip**: Dirección IP asignada.
- **nombre**: Hostname o nombre de red.
- **nombre_usuario**: Cuenta de usuario local asignada.
- **estado**: Estado administrativo (`LIBRE`, `EN USO`, `BAJA`).
- **estado_fisico**: Clasificación del estado físico (`BUENO`, `REGULAR`, `MALO`).
- **vida_util**: Vida útil estimada en años.
- **observacion**: Observación técnica del equipo.

### B. Especificaciones Técnicas (Características)
- Atributos de hardware registrados (ej. Procesador, Memoria RAM, Disco Duro, Marca, Modelo, etc.).

### C. Contexto de Software (Programas Instalados)
- Lista de programas de software asignados al equipo, incluyendo:
  - **nombre**: Nombre del programa.
  - **cod_informatica**: Código de informática del programa.
  - **tipo**: Categoría o tipo de software (Institucional, Navegador, Ofimática, Soporte, Antivirus, etc.).
  - **estado**: Estado del programa.

### D. Contexto de Custodia (Responsable Asignado)
- Información del custodio o responsable actual del equipo:
  - **nombre_completo**: Nombre completo del responsable.
  - **cargo**: Cargo que ocupa en la institución.
  - **celular**: Número de celular de contacto.
  - **correo**: Correo electrónico institucional.
  - **oficina**: Nombre de la oficina donde labora.
  - **area**: Área institucional a la que pertenece la oficina.

### E. Historial de Cambios (Movimientos)
- Historial de los últimos 15 movimientos o acciones registradas sobre el equipo:
  - **fecha_hora**: Fecha y hora del movimiento.
  - **accion**: Tipo de acción realizada (`CREACION`, `MODIFICACION`, `ELIMINACION`).
  - **descripcion**: Detalles específicos de los cambios realizados.

### F. Consulta del Operador
- **pregunta**: Texto o consulta redactada por el usuario en tiempo real desde el drawer.

---

## 🌐 3. Proveedores de IA y Mecanismo de Tolerancia a Fallos (Failover)

El sistema de Inventario de la UGEL cuenta con una arquitectura de alta disponibilidad y tolerancia a fallos para garantizar la continuidad de las funciones inteligentes frente a límites de cuotas, saturación o caídas de los proveedores:

### A. Proveedores Soportados
1. **Gemini (Directo)**: Conexión nativa con la API de Google Gemini utilizando el modelo `gemini-2.5-flash` por defecto.
2. **OpenRouter (Fallback)**: Conexión mediante API HTTP a OpenRouter, que actúa como puente de failover transparente y ofrece una cola secuencial de modelos de contingencia de código abierto y privativos.
3. **Mock (Simulacro)**: Driver local para desarrollo y pruebas offline que simula las respuestas de la IA sin consumir tokens de APIs reales.

### B. Secuencia de Ejecución y Fallback Automático
- El sistema resuelve las peticiones a través de la interfaz singleton `AIService` gestionada por `AIManager` (`app/Services/AI/AIManager.php`).
- **Flujo de Ejecución Secuencial**:
  1. Se intenta resolver la petición con el driver primario configurado en la variable `AI_DRIVER` del archivo `.env` (por defecto, `openrouter` o `gemini`).
  2. Si el driver primario (ej. `gemini`) falla, lanza una excepción o alcanza su límite de cuota, el sistema captura el error e inserta una advertencia (`logger()->warning`) en el log local `storage/logs/laravel.log`.
  3. Se activa de forma transparente el **Failover a OpenRouter**, el cual rota de manera secuencial sobre un arreglo de modelos configurados en `config/services.php`:
     - `google/gemini-2.5-flash`
     - `meta-llama/llama-3.3-70b-instruct`
     - `deepseek/deepseek-chat`
     - `qwen/qwen-2.5-72b-instruct`
  4. Si alguno de los modelos de OpenRouter responde exitosamente, se detiene el bucle, se almacena en el ciclo de vida de la petición qué proveedor y modelo resolvieron la consulta y se devuelve el resultado.
  5. Si todos los proveedores e intentos de modelos fallan, se propaga una excepción consolidada hacia el controlador.

### C. Transparencia y Trazabilidad (Metadatos de Respuesta)
- Cada respuesta del controlador hacia el frontend incluye las propiedades:
  - `provider`: El proveedor que resolvió la petición (`Gemini`, `OpenRouter` o `Mock`).
  - `model`: El modelo específico que generó la respuesta (ej. `google/gemini-2.5-flash`, `deepseek-chat`).
- Estos datos son presentados en tiempo real en la interfaz del Drawer del equipo mediante un **Badge Visual Premium** animado en el pie de página de la respuesta, lo que permite a los operadores auditar los consumos y conocer qué inteligencia procesó el diagnóstico.
