# 9Eleven — Sistema de Gestión de Inventario para Minimarket

Aplicación web para controlar el inventario de un minimarket: productos, categorías, proveedores, ventas, alertas de stock y reportes. Desarrollada con **PHP nativo + MariaDB/MySQL**, siguiendo la metodología **ICONIX**.

---

## Tabla de contenido

1. [Problema que resuelve](#-problema-que-resuelve)
2. [Funcionalidades](#-funcionalidades)
3. [Tecnologías](#-tecnologías)
4. [Arquitectura y estructura del proyecto](#-arquitectura-y-estructura-del-proyecto)
5. [Base de datos](#-base-de-datos)
6. [Instalación local (XAMPP)](#-instalación-local-xampp)
7. [Despliegue en producción](#-despliegue-en-producción)
8. [Guía para nuevos colaboradores](#-guía-para-nuevos-colaboradores)
9. [Estado del proyecto y roadmap](#-estado-del-proyecto-y-roadmap)
10. [Documentación del proyecto](#-documentación-del-proyecto)
11. [Equipo](#-equipo)

---

## Problema que resuelve

El minimarket 9Eleven no tenía un sistema centralizado de inventario. En el levantamiento de información (observación, entrevistas y encuestas) se encontraron tres problemas:

| # | Hallazgo |
|---|----------|
| 1 (crítico) | No hay notificación de escasez: los productos se agotan antes de pedir al proveedor. |
| 2 | Datos de proveedores (contactos y precios de costo) dispersos en facturas de papel. |
| 3 | No existen reportes consolidados con el valor total del inventario. |

**Objetivo:** automatizar el control de inventario, ordenar la relación con proveedores y entregar reportes útiles para decisiones de gerencia.

---

## Funcionalidades

El sistema tiene **un único rol: Administrador** (acceso total).

| Módulo | Qué hace |
|--------|----------|
| **Index** | Página principal y acceso al login. |
| **Autenticación** | Inicio de sesión, cambio de contraseña y cierre de sesión. |
| **Dashboard** | Resumen en tiempo real de productos, stock, proveedores y ventas. |
| **Productos** | Registrar, consultar y eliminar productos (código, precios de compra/venta, IVA, stock actual y mínimo). |
| **Categorías** | Crear, consultar y eliminar categorías. |
| **Proveedores** | Registrar, consultar y eliminar proveedores. |
| **Ventas** | Registro e historial de ventas (fecha, productos, cantidades). |
| **Alertas de stock** | Aviso cuando un producto llega o cae por debajo de su stock mínimo. |
| **Reportes** | Movimientos de inventario, productos más vendidos, stock bajo y valor total del inventario. |

**Fuera de alcance:** múltiples roles, app móvil nativa, versión de escritorio, integración con POS externos y facturación electrónica.

---

## Tecnologías

| Capa | Tecnología |
|------|-----------|
| Lenguaje backend | PHP nativo, sin framework (desarrollado con PHP 8.2; el documento funcional indica 8.3) |
| Servidor web | Apache 2.4 |
| Base de datos | MariaDB (desarrollada con 10.4.32; el documento indica 10.11). Compatible con MySQL |
| Acceso a datos | PDO con excepciones y *prepared statements* reales (`EMULATE_PREPARES = false`) |
| Sesiones | Sesiones nativas de PHP; la sesión activa se guarda en `$_SESSION['admin_id']` |
| Frontend | HTML + CSS propio (`assets/css/style.css`). Sin framework ni librerías; el único JavaScript es un `confirm()` en línea al eliminar |
| Entorno de desarrollo | XAMPP |

---

## Arquitectura y estructura del proyecto

Arquitectura **cliente–servidor** clásica: el navegador hace peticiones a páginas PHP en Apache; cada página ejecuta su lógica y consulta MariaDB vía PDO.

```
Navegador ──HTTP──▶ Apache + PHP 8.3 ──PDO──▶ MariaDB
```

Estructura de archivos según el repositorio original:

```
9eleven/
├── index.php               # Página principal / login
├── dashboard.php           # Panel con resumen general
├── productos.php           # Módulo de productos
├── categorias.php          # Módulo de categorías
├── proveedores.php         # Módulo de proveedores
├── ventas.php              # Módulo de ventas
├── reportes.php            # Módulo de reportes
├── cambiarContrasena.php   # Cambio de contraseña (pide email y contraseña actual)
├── alertaStock.php         # Alertas de stock (enlazado desde el menú)
├── cerrarSesion.php        # Cierre de sesión (enlazado desde el header)
├── config.php              # Conexión PDO + helpers (se incluye en TODAS las páginas)
├── db_9eleven.sql          # Script de la base de datos
├── assets/
│   ├── css/style.css       # Estilos
│   └── img/                # Imágenes
└── includes/
    ├── header.php          # Cabecera común
    ├── sidebar.php         # Menú lateral
    └── footer.php          # Pie de página
```

**Convención del proyecto:** cada módulo vive en un solo archivo `.php` y las piezas de interfaz repetidas están en `includes/`.

### `config.php` — lo primero que hay que leer

Se incluye al inicio de cada página y hace tres cosas:

1. Inicia la sesión (`session_start()`).
2. Crea la conexión `$pdo` (host `127.0.0.1`, base `db_9eleven`, usuario `root`, sin contraseña: los valores por defecto de XAMPP).
3. Define funciones auxiliares que debes usar en vez de reinventarlas:

| Función | Para qué sirve |
|---------|----------------|
| `requerirLogin()` | Ponla al inicio de **toda** página protegida; redirige a `index.php` si no hay sesión. |
| `estaLogueado()` | `true` si hay un administrador con sesión activa. |
| `h($texto)` | Escapa texto para imprimirlo en HTML de forma segura (anti-XSS). |
| `formatoPesos($valor)` | Formatea pesos colombianos: `1200` → `$1.200`. |

> ⚠️ El diagrama de paquetes del documento funcional plantea archivos separados (`nuevoProducto.php`, `consultarProducto.php`, `eliminarProducto.php`, …), pero el código actual los agrupa por módulo. Se considera la estructura real la de arriba. El menú lateral se define en `includes/sidebar.php` (arreglo `$items`) y el enlace *Log Out* está en `includes/header.php`.

---

## Base de datos

Motor: MariaDB 10.11. Script de creación: [`db_9eleven.sql`](db_9eleven.sql).

Motor: MariaDB. Script de creación e importación de datos de ejemplo: [`db_9eleven.sql`](db_9eleven.sql) (crea las tablas, relaciones y unos pocos datos de prueba; la base se llama `db_9eleven`).

### Modelo

```
administrador ──< ventas ──< detalle_ventas >── productos >── categorias
                                                                  ^
                                          proveedores ────────────┘
```

| Tabla | Descripción | Campos principales |
|-------|-------------|--------------------|
| `administrador` | Credenciales de acceso | `ID_Administrador` (PK), `email` (UNIQUE), `contrasena` |
| `categorias` | Tipos de producto | `ID_Categoria` (PK), `nombreCategoria` |
| `productos` | Catálogo con precios y stock | `ID_Producto` (PK), `codigoProducto` (UNIQUE), `nombreProducto`, `informacionGeneral`, `precioCompra`, `precioVenta`, `iva`, `stockActual`, `stockMinimo`, `ID_Categoria` (FK) |
| `proveedores` | Empresas que surten mercancía | `ID_Proveedor` (PK), `nombreEmpresa`, `telefono`, `contacto`, `ID_Categoria` (FK) |
| `ventas` | Encabezado de cada venta | `ID_Venta` (PK), `fechaVenta` (DATE), `totalVenta`, `ID_Administrador` (FK) |
| `detalle_ventas` | Líneas de cada venta | `ID_Detalle` (PK), `ID_Venta` (FK), `ID_Producto` (FK), `cantidad`, `precioUnitario`, `subtotal` |

Datos de ejemplo incluidos: 1 administrador, 4 categorías (Bebidas, Snacks, Lácteos, Aseo), 3 productos, 2 proveedores y 1 venta.

### Decisiones de diseño que hay que conocer

- **No existen las tablas `reportes` ni `alerta_stock`**, a propósito. Los reportes y las alertas se **calculan con consultas SQL** (por ejemplo, una alerta es un producto con `stockActual <= stockMinimo`). Por eso el diagrama ER del documento funcional, que sí las dibuja, está desactualizado respecto al script.
- **El stock vive en `productos.stockActual`**, con el mínimo en `productos.stockMinimo`.
- **Una venta se guarda en dos tablas:** el encabezado en `ventas` y cada producto vendido en `detalle_ventas`, con el precio unitario del momento.
- **Qué pasa al borrar (claves foráneas):**

| Si borras… | Efecto |
|-----------|--------|
| una categoría | `productos.ID_Categoria` y `proveedores.ID_Categoria` quedan en `NULL` |
| una venta | se borran sus líneas de `detalle_ventas` (CASCADE) |
| un producto que ya se vendió | la base lo **rechaza** (no hay `ON DELETE`) |
| el administrador con ventas | la base lo **rechaza** |

- **Falta una tabla de entradas de mercancía.** El requerimiento RF-12 pide registrar fecha, proveedor, producto, cantidad y precio unitario, y el modelo no lo soporta. Ver [`ISSUES.md`](ISSUES.md).
- **Las contraseñas están en texto plano** en el script de ejemplo. Ver la advertencia de seguridad más abajo.

---

## Instalación local (XAMPP)

### Requisitos

- [XAMPP](https://www.apachefriends.org/) con **PHP 8.3** y **MariaDB 10.11** (o cualquier stack Apache + PHP ≥ 8.1 + MySQL/MariaDB).
- Git.

### Pasos

1. **Clonar** el repositorio dentro de la carpeta pública de XAMPP:

   ```bash
   cd C:\xampp\htdocs          # Linux/macOS: /opt/lampp/htdocs
   git clone https://github.com/<usuario>/<repositorio>.git 9eleven
   ```

2. **Iniciar** Apache y MySQL desde el panel de XAMPP.

3. **Crear la base de datos e importar el script:**
   - Abrir `http://localhost/phpmyadmin`.
   - Pestaña **Importar** → seleccionar `db_9eleven.sql`.
   - Si phpMyAdmin pide una base primero, crea `db_9eleven` (cotejamiento `utf8mb4_unicode_ci`) y luego importa dentro de ella. El nombre debe coincidir con `DB_NAME` en `config.php`.

4. **Revisar la conexión.** `config.php` ya viene con los valores por defecto de XAMPP (`127.0.0.1`, base `db_9eleven`, usuario `root`, sin contraseña), así que normalmente no hay que tocar nada. Si tu MySQL tiene contraseña o usa otro puerto, edita las constantes `DB_*` al inicio del archivo.

5. **Abrir** `http://localhost/9eleven/`.

6. **Iniciar sesión** con el administrador de prueba que crea el script: correo `admin@9eleven.com`. La contraseña está en la sentencia `INSERT INTO administrador` de `db_9eleven.sql`.

### Problemas comunes

| Síntoma | Causa probable |
|---------|----------------|
| "Connection refused" / error PDO | MySQL no está iniciado o los datos de `config.php` son incorrectos. |
| Caracteres raros (ñ, tildes) | Base o conexión sin `utf8mb4`. |
| El correo de recuperación nunca llega | XAMPP no trae servidor de correo configurado. Ver issue de recuperación de contraseña. |
| Página en blanco | Activar `display_errors` en `php.ini` en desarrollo. |

---

## Limitaciones de seguridad conocidas

Este es un proyecto académico pensado para correr **en local (XAMPP) con datos de ejemplo**. Antes de usarlo con datos reales o ponerlo en internet hay que resolver lo siguiente. Son tareas abiertas para quien continúe el proyecto (búscalas en los [issues](../../issues) con la etiqueta `security`):

- Las contraseñas se guardan **en texto plano** (la columna `contrasena` guarda el valor tal cual). Deben guardarse con `password_hash()` y validarse con `password_verify()`.
- El login acepta contraseñas en texto plano a propósito, para los datos de ejemplo. Basta con guardar hashes en la base y quitar esa rama del código.
- No hay protección CSRF en los formularios ni `session_regenerate_id()` al iniciar sesión.
- `cambiarContrasena.php` es pública y no limita intentos: permite probar contraseñas sin freno.
- Si falla la conexión, `config.php` muestra el mensaje del error en pantalla, que puede revelar usuario y servidor.

> La contraseña del administrador que trae `db_9eleven.sql` es **solo de prueba**. No la reutilicen en ningún otro sitio y cambienla si instalan el sistema en un servidor accesible desde internet.

---

## Despliegue en producción

El proyecto no necesita compilación: basta un servidor con PHP y MariaDB.

### Opción A — Hosting compartido (cPanel)

1. Subir los archivos a `public_html/` (FTP o administrador de archivos).
2. Crear base de datos y usuario en **MySQL Databases**; importar `db_9eleven.sql` desde phpMyAdmin.
3. Crear `config.php` con las credenciales del hosting.
4. Activar **HTTPS** (Let's Encrypt / SSL del hosting).
5. Seleccionar **PHP 8.3** en el selector de versión.

### Opción B — VPS (Ubuntu + Apache)

```bash
sudo apt update
sudo apt install apache2 php8.3 php8.3-mysql mariadb-server git
sudo mysql_secure_installation

# Base de datos con usuario dedicado (NO usar root)
sudo mysql -e "CREATE DATABASE db_9eleven CHARACTER SET utf8mb4;
CREATE USER '9eleven'@'localhost' IDENTIFIED BY 'CAMBIAR_ESTA_CLAVE';
GRANT ALL ON db_9eleven.* TO '9eleven'@'localhost';"
sudo mysql db_9eleven < db_9eleven.sql

# Código
sudo git clone https://github.com/<usuario>/<repositorio>.git /var/www/9eleven
sudo nano /var/www/9eleven/config.php   # usuario y contraseña de BD (constantes DB_*)
sudo chown -R www-data:www-data /var/www/9eleven
```

Después: configurar un *VirtualHost* apuntando a `/var/www/9eleven` y activar HTTPS con `certbot --apache`.

### Checklist antes de publicar

- [ ] Las credenciales de producción **no** están en el repositorio (ver issue sobre configuración por entorno).
- [ ] Usuario de BD con permisos limitados (no `root`).
- [ ] Contraseñas hasheadas y contraseña del administrador inicial cambiada.
- [ ] `display_errors = Off` en `php.ini`.
- [ ] HTTPS activo.
- [ ] Copias de seguridad programadas de la base de datos.

---

## Guía para nuevos colaboradores

### Cómo está pensado el código

- Cada módulo (`productos.php`, `ventas.php`, …) mezcla la lógica PHP y la vista HTML en el mismo archivo.
- `config.php` se incluye en todas las páginas: da la conexión `$pdo` y los helpers (`requerirLogin()`, `h()`, `formatoPesos()`).
- Cabecera, menú y pie salen de `includes/`; si cambias el menú, lo haces en `sidebar.php` una sola vez.
- Los estilos están centralizados en `assets/css/style.css`.

### Anatomía de una página

Todas las páginas siguen el mismo orden (tómalo como plantilla):

```php
<?php
require_once 'config.php';   // sesión + $pdo + helpers
requerirLogin();             // solo en páginas protegidas
$paginaActual = 'productos.php';

// 1) Procesar acciones POST (cada botón tiene name="accion_...")
// 2) Consultar datos con $pdo->prepare(...)
// 3) HTML: include header + sidebar, contenido, include footer
```

- **Login** (`index.php`): busca al administrador por `email` y guarda `$_SESSION['admin_id']` y `$_SESSION['admin_email']`. Acepta contraseñas hasheadas (`password_verify`) **y** en texto plano (`hash_equals`); esto último existe solo para los datos de ejemplo.
- **Vistas dentro de un módulo:** `productos.php` usa `?vista=lista|nuevo|consultar|eliminar` para mostrar distintos formularios en la misma página; las acciones llegan por POST (`accion_nuevo`, `accion_eliminar`, `accion_consultar`).
- **Mensajes:** variables `$error` y `$exito`, impresas con las clases CSS `mensaje-error` y `mensaje-ok`.
- **Menú:** `includes/sidebar.php` tiene un arreglo `$items` (archivo => etiqueta). Para agregar un módulo, crea el `.php`, define `$paginaActual` con su nombre de archivo y añade la entrada al arreglo; así se resalta como activo.
- **Alerta de stock bajo:** un producto está "bajo" cuando `stockActual <= stockMinimo`. La misma regla se usa en el dashboard y en productos.

### Flujo de trabajo con Git

```bash
git checkout -b feature/nombre-corto      # una rama por tarea
# ... trabajar ...
git commit -m "feat(productos): agregar edición de productos"
git push origin feature/nombre-corto
# abrir Pull Request hacia main
```

- Ramas: `feature/…`, `fix/…`, `docs/…`.
- Commits sugeridos: `feat:`, `fix:`, `docs:`, `refactor:`.
- Todo cambio entra por **Pull Request** con al menos una revisión.
- Mira las [issues](../../issues) con la etiqueta `good first issue` para empezar.

### Buenas prácticas que pedimos

- Usar **consultas preparadas** (PDO `prepare`/`execute`) — nunca concatenar variables en SQL.
- Llamar `requerirLogin()` al inicio de toda página protegida.
- Imprimir datos con `h()`; mostrar dinero con `formatoPesos()`.
- No subir credenciales ni datos reales al repositorio.
- Probar a mano el flujo completo del módulo que tocas antes de abrir el PR.

---

## Estado del proyecto y roadmap

### Qué hace hoy cada módulo (verificado en el código)

| Módulo | Estado actual |
|--------|---------------|
| Login (`index.php`) | Funciona. Mensaje de error genérico. Sin protección contra fuerza bruta ni `session_regenerate_id`. |
| Dashboard | Muestra 4 indicadores (productos, alertas, proveedores, ventas de hoy), los últimos 6 movimientos y las 6 alertas con menos stock. |
| Productos | Crear, consultar (filtro por nombre y categoría) y eliminar. **No se puede editar.** Eliminar funciona **por nombre exacto** y no captura el error si el producto ya tiene ventas. |
| Ventas | **Solo lectura:** lista las ventas de una fecha y su detalle. **No hay forma de registrar una venta** (las existentes vienen del script SQL). Por lo tanto el stock nunca se descuenta. |
| Alertas de stock | `alertaStock.php` lista **todos** los productos con `stockActual <= stockMinimo`, ordenados por faltante, con etiqueta `CRÍTICO` (stock 0) o `BAJO`. Además hay un resumen de 6 en el dashboard y la columna *Estado* en productos. No muestra categoría ni proveedor. |
| Categorías | Crear, consultar y eliminar (por nombre). Muestra cuántos productos tiene cada una. **No se puede editar** y permite nombres duplicados. |
| Proveedores | Crear, consultar y eliminar (por nombre). **No se puede editar.** No se relacionan con productos ni con compras: solo guardan empresa, contacto, teléfono y categoría. |
| Reportes | Solo 1 de los 4 reportes pedidos: **productos más vendidos** (con filtro), más dos indicadores del mes. Faltan movimientos de inventario, stock bajo y valor del inventario. |
| Cambiar contraseña | Pública; pide email, contraseña actual y la nueva (mínimo 6 caracteres) y guarda con `password_hash`. **Cambia, no recupera:** si se olvida la clave, no hay forma de restablecerla. |
| Cerrar sesión | `cerrarSesion.php` vacía `$_SESSION`, destruye la sesión y redirige al login. No borra la cookie de sesión y se activa con un simple enlace (GET). |

### Pendientes principales

El documento de revisión de requerimientos aprobó el alcance pero dejó **tres hallazgos pendientes**; al revisar el código se sumaron otros. Los principales:

| Pendiente | Origen |
|-----------|--------|
| Editar productos, categorías y proveedores (hoy solo hay crear/consultar/eliminar) | CU 2.2.4, 2.3.4, 2.4.4 |
| Registro de **entradas de mercancía** con proveedor, producto, cantidad y precio | RF-12 |
| Caso de uso explícito de **nueva venta** | CU 2.5.1 |
| Reportes que faltan: movimientos de inventario, stock bajo y valor del inventario | RF-14 |
| Recuperar una contraseña olvidada por correo | RF-03 / CU 1.2 |

El listado completo de tareas, con descripción y criterios de aceptación, está en [`ISSUES.md`](ISSUES.md).

---

## Documentación del proyecto

El **Documento Funcional** (PDF) incluye: acta de apertura, hallazgos, prototipos de interfaz (GUI), levantamiento y revisión de requerimientos, casos de uso, diagramas de robustez, estado, actividad y secuencia, diagrama de clases, ER extendido, paquetes, componentes y diseño de datos.

Se recomienda guardarlo en `docs/` dentro del repositorio.

---

## Equipo

| Integrante | Rol en el proyecto |
|------------|--------------------|
| Felipe Nuñez Navarro | Diseño de interfaces y arquitectura del sistema; modelo lógico de la BD |
| Jorge Humberto Leyes Rojas | Modelado y arquitectura (casos de uso, clases, paquetes, estados) |
| Manuel Alejandro Mera Ruiz | Requisitos, modelado de procesos y datos (actividad, ER); desarrollo backend/frontend |
