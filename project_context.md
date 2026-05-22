# AI_PROJECT_CONTEXT: InventarioUGEL

[STACK]
Backend: Laravel ^12.0 | PHP ^8.2 | Jetstream/Sanctum | Inertia-Laravel ^2.0 | DomPDF ^3.1
Frontend: Vue 3 (^3.3.13) | Inertia.js ^2.0 | Vite ^7.0.7 | Tailwind ^3.4.0 | Axios | Qrcode.vue
Database: MySQL (inventario_ugel)
AI Service: Gemini (via AIController using GEMINI_API_KEY)

[TREE]
/
├─ app/
│  ├─ Http/Controllers/
│  │  ├─ AIController.php (Gemini API integrations)
│  │  ├─ ArchivoInventarioController.php (Upload/manage Excel files)
│  │  ├─ AreaController.php (CRUD area)
│  │  ├─ CaracteristicaEquipoController.php (CRUD specs)
│  │  ├─ Controller.php (Base)
│  │  ├─ CronogramaMantenimientoController.php (Campaign maintenance)
│  │  ├─ DashboardController.php (Stats / overview metrics)
│  │  ├─ EquipoController.php (CRUD equipment / hardware & software licenses)
│  │  ├─ HistorialMovimientoController.php (Asset activity log / audit)
│  │  ├─ ItemCronogramaController.php (Maintenance events by office)
│  │  ├─ MantenimientoController.php (Details of technical support)
│  │  ├─ OficinaController.php (CRUD office)
│  │  ├─ PersonaController.php (CRUD staff members)
│  │  ├─ ReporteController.php (Exporting Excel/PDF reports)
│  │  └─ UsuarioController.php (CRUD IT users)
│  └─ Models/
│     ├─ ArchivoInventario.php (Model: files uploaded)
│     ├─ Area.php (Model: areas)
│     ├─ CaracteristicaEquipo.php (Model: specifications - EAV-like table)
│     ├─ CronogramaMantenimiento.php (Model: maintenance programs)
│     ├─ Equipo.php (Model: devices & programs)
│     ├─ HistorialMovimiento.php (Model: logs/audit)
│     ├─ ItemCronograma.php (Model: scheduled maintenance item per office)
│     ├─ Mantenimiento.php (Model: individual interventions)
│     ├─ Oficina.php (Model: offices)
│     ├─ Persona.php (Model: staff)
│     └─ User.php (Model: IT operators / admins)
├─ database/
│  ├─ migrations/
│  └─ seeders/
├─ resources/
│  ├─ js/
│  │  ├─ Components/ (Shared UI elements, inputs, buttons, tables)
│  │  ├─ Layouts/ (AppLayout, GuestLayout)
│  │  ├─ Pages/ (Views rendered by Inertia.js)
│  │  │  ├─ API/ [Index, Partials/ApiTokenManager]
│  │  │  ├─ Archivos/ [Index, Partials/ModalAgregar, Partials/ModalReportes]
│  │  │  ├─ Areas/ [Index, Partials/ModaCrear, ModalEditar, ModalEliminar]
│  │  │  ├─ Auth/ [ConfirmPassword, ForgotPassword, Login, Register, ResetPassword, TwoFactorChallenge, VerifyEmail]
│  │  │  ├─ Dashaboard/ [Index]
│  │  │  ├─ Inventario/ [Index, Show, Partials/* (ModaCrear, ModalEditar, ModalEliminar, ModalFichaTecnica, ModalReportes)]
│  │  │  ├─ Miembros/ [Index, Partials/* (ModaCrear, ModalEditar, ModalEliminar)]
│  │  │  ├─ Oficinas/ [Index, Partials/* (ModaCrear, ModalEditar)]
│  │  │  ├─ Personas/ [Index, Partials/* (ModaCrear, ModalEditar, ModalEliminar)]
│  │  │  ├─ PlanMantenimiento/ [Index, Public, Show, Partials/* (ModaCrear, ModalEditar, ModalEliminar, Actividades/*)]
│  │  │  ├─ Profile/ [Show, Partials/* (DeleteUserForm, LogoutOtherBrowserSessionsForm, TwoFactorAuthenticationForm, UpdatePasswordForm, UpdateProfileInformationForm)]
│  │  │  ├─ Dashboard.vue
│  │  │  ├─ PrivacyPolicy.vue
│  │  │  ├─ TermsOfService.vue
│  │  │  └─ Welcome.vue
│  │  ├─ app.js
│  │  └─ bootstrap.js
│  └─ views/
│     ├─ emails/
│     ├─ reportes/ (Blade templates for PDF exports)
│     └─ app.blade.php (HTML shell for Inertia)
├─ routes/
│  ├─ api.php (Stateless routes)
│  ├─ console.php (Artisan commands / console schedules)
│  └─ web.php (Stateful Inertia routes with web + auth middleware)
├─ tailwind.config.js
└─ vite.config.js

[DB_SCHEMA]
- users (id PK, name, email, email_verified_at, password, remember_token, timestamps)
- areas (id PK, nombre, descripcion, timestamps)
- oficinas (id PK, nombre, descripcion, area_id FK -> areas.id, timestamps)
- personas (id PK, nombre_completo, celular, correo, cargo, estado[ACTIVO|INACTIVO], id_oficina FK -> oficinas.id, timestamps)
- equipos (id PK, cod_informatica UNIQUE, cod_patrimonial UNIQUE, nombre, nombre_usuario, tipo[PC|Laptop|Monitor|etc], estado[LIBRE|EN USO|BAJA], categoria[equipo|programa], clasificacion[BUENO|REGULAR|MALO], ip, fecha_ingreso, fecha_disponible_uso, vida_util_anios, observacion_tecnica, id_persona FK -> personas.id NULL, timestamps)
- caracteristica_equipos (id PK, clave, valor, id_equipo FK -> equipos.id, timestamps)
- cronograma_mantenimientos (id PK, titulo, descripcion, timestamps)
- item_cronogramas (id PK, id_oficinas FK -> oficinas.id, id_cronograma FK -> cronograma_mantenimientos.id, fecha_inicio, fecha_fin, actividad, estado[Pendiente|Cancelado|Completado], timestamps)
- mantenimientos (id PK, fecha_realizada, observaciones, realizado[bool], id_equipo FK -> equipos.id, id_cronograma FK -> cronograma_mantenimientos.id NULL, id_usuario FK -> users.id, timestamps)
- historial_movimientos (id PK, tipo_accion[CREACION|MODIFICACION|ELIMINACION], descripcion, fecha_hora, id_usuario FK -> users.id, id_equipo FK -> equipos.id NULL, timestamps)
- archivo_inventarios (id PK, nombre_archivo, ruta, fecha_carga, id_usuario FK -> users.id, timestamps)

[ROUTES]
- GET / -> Redirect: Login if guest, Dashboard if auth
- GET /cronograma-mantenimiento -> CronogramaMantenimientoController@publicShow [Guest OK]
- GROUP [auth:sanctum, verified]:
  ├─ GET /dashboard -> DashboardController@index
  ├─ Resource /areas -> AreaController
  ├─ Resource /personas -> PersonaController
  ├─ Resource /oficinas -> OficinaController
  ├─ Resource /miembros -> UsuarioController
  ├─ Resource /mantenimiento -> CronogramaMantenimientoController (PlanMantenimiento folder)
  ├─ POST /mantenimiento/{mantenimiento}/actividades -> ItemCronogramaController@store
  ├─ PUT /mantenimiento/actividades/{actividad} -> ItemCronogramaController@update
  ├─ Resource /archivos -> ArchivoInventarioController
  ├─ GET /api/oficinas -> Closure (returns JSON: id, nombre, area:id,nombre)
  ├─ Resource /api/caracteristicas -> CaracteristicaEquipoController
  ├─ POST /reportes/equipos/pdf -> ReporteController@equiposPdf
  ├─ POST /reportes/equipos/excel -> ReporteController@equiposExcel
  ├─ POST /api/ai/mejorar-observacion -> AIController@mejorarObservacionTecnica (calls Gemini)
  ├─ POST /api/ai/diagnosticar-equipo -> AIController@diagnosticarEquipo (calls Gemini)
  └─ Prefix /inventario:
     ├─ GET /equipos -> EquipoController@index
     ├─ GET /programas -> EquipoController@index (filtered by category='programa')
     ├─ POST / -> EquipoController@store
     ├─ GET /equipo/{cod_informatica} -> EquipoController@showByCodigo
     ├─ GET /{equipo} -> EquipoController@show
     ├─ PUT /{equipo} -> EquipoController@update
     ├─ DELETE /{equipo} -> EquipoController@destroy
     └─ GET /{equipo}/historial -> HistorialMovimientoController@getHistorial

[DEV_PATTERNS]
1. Inertia response: `Inertia::render('Folder/Page', $data)`. Vue uses `<script setup>` with `defineProps` and `@inertiajs/vue3` for routes/forms.
2. Data validation inside Controllers: `$request->validate([...])` (fields match DB Schema constraints).
3. DB transaction wrapper for multi-entity edits: `DB::transaction(fn() => ...)` or explicit begin/commit/rollback.
4. Eager loading relationships in controllers: e.g., `with(['area'])` or `with(['persona.oficina.area'])` to prevent N+1 issues.
5. Audit Logs: Call `HistorialMovimientoController` or directly insert into `historial_movimientos` upon creating/updating/deleting `equipos`.
6. Gemini Integration: Utilizes Gemini API. Structured prompt templates for technical text expansion and diagnostic generation are inside `AIController.php`.
7. CSS Styling: Vanilla Tailwind CSS (classes integrated in Vue components). Check `.config` for customizations. Do not override layout core metrics without review.
