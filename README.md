# SHALOM - Sistema Reportes DL

## Instalación rápida

1. XAMPP: Apache + MySQL activos
2. http://localhost/SHALOM/install.php → Instalar
3. http://localhost/SHALOM/upgrade.php → Actualizar módulo personal (si aplica)
4. Entrada directa: **http://localhost/SHALOM/** (abre `index.php`)

**Usuario demo:** admin@shalom.local / admin123

> El proyecto es **100% PHP**. No hay archivos `.html`. Si guardaste enlaces viejos (`manana.html`, etc.), Apache los redirige automáticamente vía `.htaccess`.

## Estructura

- `config/` - Base de datos
- `includes/` - Layout, auth, helpers
- `auth/` - Login, registro
- `api/` - Cargueros JSON
- `modules/` - Perfil, mantenimiento, cargueros, personal
- `assets/` - CSS y JS
- `database/schema.sql`

## Módulos

- **Cargueros** - Turno mañana/tarde, guardado MySQL
- **Personal** - Trabajadores, asistencia, programación, reportes
- **Mantenimiento** - CRUD flota
- **Perfil** - Datos y contraseña
