# pwa/

Copia aquí el proyecto **leodri.peV2**.

## Configuración por entorno

| Archivo | Uso |
|---------|-----|
| `config/database.local.php` | Local → MySQL Docker (copiar desde `database.local.example.php`) |
| `config/database.php` | cPanel → MySQL Railway (copiar desde `database.example.php`) |
| `js/leodri-config.js` | Base producción (Railway); va a cPanel |
| `js/leodri-config.local.js` | Local → stock en `localhost:3000` (copiar desde `.local.example.js`) |

Prioridad PHP: `database.local.php` gana sobre `database.php` si existe.

Prioridad JS: `leodri-config.local.js` se carga después y sobrescribe la config base.

Ver [docs/SETUP-LOCAL.md](../docs/SETUP-LOCAL.md) y [docs/DESPLIEGUE.md](../docs/DESPLIEGUE.md).

## Servir en local

```powershell
cd E:\PROYECTOS\ProyectoLeodri\docker
docker compose up -d

cd E:\PROYECTOS\ProyectoLeodri\pwa
php -S localhost:8080
```

Despliegue producción: FTP/cPanel → `leodri.pe` (sin archivos `*.local.*`).
