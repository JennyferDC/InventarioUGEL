# Diagrama de Base de Datos - Sistema InventarioUGEL

Este artefacto contiene el **Diagrama de Entidad-Relación (ERD)** de la base de datos de negocio del sistema **InventarioUGEL**. Se han omitido de forma intencional las tablas de infraestructura y por defecto de Laravel (como `cache`, `jobs`, `failed_jobs`, `sessions`, `password_reset_tokens` y `personal_access_tokens`) para focalizarse al 100% en las entidades de dominio y sus relaciones.

---

## 1. Diagrama de Entidad-Relación (ERD)

A continuación se muestra el modelo físico representado en un diagrama de Mermaid:

```mermaid
erDiagram
    users {
        bigint id PK
        string name "Nombre del administrador/operador"
        string email "Correo de login"
        timestamp email_verified_at
        string password
        string remember_token
        timestamps timestamps
    }

    areas {
        bigint id PK
        string nombre "Nombre del área institucional"
        string descripcion "Detalles opcionales"
        timestamps timestamps
    }

    oficinas {
        bigint id PK
        string nombre "Nombre de la oficina"
        text descripcion "Detalles opcionales"
        bigint area_id FK "Relación con areas"
        timestamps timestamps
    }

    personas {
        bigint id PK
        string nombre_completo "Colaborador responsable"
        string celular "Teléfono de contacto"
        string correo "Dirección de correo electrónico"
        string cargo "Puesto ocupado"
        enum estado "ACTIVO | INACTIVO"
        bigint id_oficina FK "Oficina física a la que pertenece"
        timestamps timestamps
    }

    equipos {
        bigint id PK
        string cod_informatica "Código único interno de informática"
        string cod_patrimonial "Código de control de patrimonio"
        string nombre "Nombre / Hostname del dispositivo"
        string nombre_usuario "Cuenta de usuario local de Windows/Linux"
        string tipo "PC | Laptop | Monitor | etc."
        string estado "LIBRE | EN USO | BAJA"
        string categoria "equipo | programa"
        string clasificacion "BUENO | REGULAR | MALO"
        string ip "Dirección de red ipv4"
        date fecha_ingreso "Llegada al área de informática"
        date fecha_disponible_uso "Puesta en funcionamiento"
        integer vida_util_anios "Estimación de depreciación"
        text observacion_tecnica "Motivos de bajas o incidencias"
        bigint id_persona FK "Responsable asignado actual"
        timestamps timestamps
    }

    caracteristica_equipos {
        bigint id PK
        string clave "Ej: RAM, Procesador, Marca"
        string valor "Ej: 16GB, Intel Core i7, HP"
        bigint id_equipo FK "Equipo al que pertenece"
        timestamps timestamps
    }

    cronograma_mantenimientos {
        bigint id PK
        string titulo "Nombre de la campaña de soporte"
        text descripcion "Alcance y metas del mantenimiento"
        timestamps timestamps
    }

    item_cronogramas {
        bigint id PK
        bigint id_oficinas FK "Oficina programada"
        bigint id_cronograma FK "Campaña de mantenimiento"
        date fecha_inicio "Inicio estimado de visitas"
        date fecha_fin "Finalización de visitas"
        string actividad "Descripción de la labor a realizar"
        enum estado "Pendiente | Cancelado | Completado"
        timestamps timestamps
    }

    mantenimientos {
        bigint id PK
        date fecha_realizada "Fecha efectiva de soporte"
        text observaciones "Detalles e incidentes encontrados"
        boolean realizado "Marca de finalizado"
        bigint id_equipo FK "Equipo intervenido"
        bigint id_cronograma FK "Campaña del cronograma"
        bigint id_usuario FK "Operador TI que ejecutó el soporte"
        timestamps timestamps
    }

    historial_movimientos {
        bigint id PK
        string tipo_accion "CREACION | MODIFICACION | ELIMINACION"
        string descripcion "Detalles formateados en mayúsculas"
        datetime fecha_hora "Fecha y hora del cambio"
        bigint id_usuario FK "Operador responsable del cambio"
        bigint id_equipo FK "Equipo auditado (Null si se eliminó)"
        timestamps timestamps
    }

    archivo_inventarios {
        bigint id PK
        string nombre_archivo "Nombre del archivo físico cargado"
        string ruta "Ubicación del archivo en almacenamiento"
        date fecha_carga "Fecha de registro"
        bigint id_usuario FK "Usuario que cargó el archivo"
        timestamps timestamps
    }

    areas ||--o{ oficinas : "contiene"
    oficinas ||--o{ personas : "alberga"
    oficinas ||--o{ item_cronogramas : "programado"
    personas ||--o{ equipos : "tiene asignado"
    equipos ||--o{ caracteristica_equipos : "especifica"
    equipos ||--o{ mantenimientos : "recibe"
    equipos ||--o{ historial_movimientos : "registra auditoría"
    cronograma_mantenimientos ||--o{ item_cronogramas : "planifica"
    cronograma_mantenimientos ||--o{ mantenimientos : "agrupa"
    users ||--o{ mantenimientos : "ejecuta"
    users ||--o{ historial_movimientos : "genera"
    users ||--o{ archivo_inventarios : "gestiona"
```

---

## 2. Descripción de Relaciones Clave de Negocio

1. **Jerarquía Estructural (`areas` ➔ `oficinas` ➔ `personas`)**:
   * Un **Área** (ej: *Administración*) contiene múltiples **Oficinas** (ej: *Abastecimiento*).
   * Cada **Oficina** alberga a varios colaboradores (**Personas**).
2. **Asignación de Activos (`personas` ➔ `equipos`)**:
   * Una **Persona** puede tener asignados uno o más **Equipos** o licencias de software (donde `estado` pasa a ser `EN USO`). Si un equipo no tiene asignado ningún responsable, su `id_persona` es `NULL` (estado `LIBRE`).
3. **Detalles Dinámicos (`equipos` ➔ `caracteristica_equipos`)**:
   * Cada **Equipo** posee múltiples filas en la tabla `caracteristica_equipos`, permitiendo agregar metadatos técnicos ilimitados (como *Procesador*, *Memoria RAM*, *Almacenamiento*, *Marca*, *Modelo*) sin requerir alterar la tabla de base de datos principal.
4. **Planificación y Mantenimiento (`cronograma_mantenimientos` ➔ `item_cronogramas` / `mantenimientos`)**:
   * Un **Cronograma de Mantenimiento** planifica visitas por oficina a través de **Item Cronogramas** definiendo rangos de fecha y estados de realización.
   * Al realizarse la intervención técnica sobre un **Equipo**, se registra un evento en **Mantenimientos** detallando la fecha real, el operador de TI responsable (`id_usuario` vinculando a `users`), y observaciones técnicas encontradas.
5. **Auditoría e Historial (`users` / `equipos` ➔ `historial_movimientos`)**:
   * Cada cambio granular o movimiento en un equipo o software (inserción, actualización de características, cambio de responsable, eliminación) queda auditado en el **Historial de Movimientos**, ligando de forma explícita quién realizó la acción (`id_usuario`) y qué equipo fue afectado (`id_equipo`).
