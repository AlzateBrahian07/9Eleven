# 📋 Issues sugeridos para 9Eleven

Cada bloque está listo para copiar a **GitHub → Issues → New issue** (el título va en *Title* y el resto en el cuerpo).

**Etiquetas sugeridas** (créalas en *Issues → Labels*): `bug`, `feature`, `security`, `database`, `docs`, `devops`, `refactor`, `good first issue`, `prioridad: alta`, `prioridad: media`, `prioridad: baja`.

**Milestones sugeridos:**
- **v1.0 – Completar alcance** → issues 2 a 8 (el #1 y #9 son urgentes y van antes)
- **v1.1 – Calidad y seguridad** → issues 9 a 13, 17 y 18
- **v2.0 – Mejoras** → issues 14 a 16


---

## 🔴 v1.0 — Completar alcance

### #1 · 🚨 Guardar contraseñas con hash y quitar la contraseña de ejemplo del SQL
**Labels:** `security`, `database`, `prioridad: alta`

**Descripción**
La columna `administrador.contrasena` guarda la contraseña **en texto plano**, y `db_9eleven.sql` trae la contraseña del administrador de ejemplo visible en el `INSERT`. Al publicar el repositorio, cualquiera podrá verla. Cualquier fuga de la base expone todas las claves.

**Tareas**
- [ ] `index.php` **ya sabe validar hashes** con `password_verify`; solo falta guardarlos. Convertir la contraseña del administrador a hash (`password_hash($clave, PASSWORD_DEFAULT)`; la columna `VARCHAR(255)` ya sirve).
- [ ] Quitar de `index.php` la rama de texto plano (`hash_equals`) cuando ya no haya claves sin hash.
- [x] `cambiarContrasena.php` ya guarda con `password_hash`: cambiar la clave desde esa pantalla convierte la contraseña a hash. El problema es que el script SQL siembra la clave en claro en **cada instalación nueva**.
- [ ] Llamar `session_regenerate_id(true)` justo después de un login correcto.
- [ ] Reemplazar la contraseña del `INSERT` del SQL por un hash, o mejor, por un script `crear_admin.php` que pida la clave al instalar.
- [ ] Cambiar la contraseña real del administrador si ya se usó en algún lado.
- [ ] Documentar en el README el usuario inicial sin escribir la clave.

**Criterios de aceptación**
- `SELECT contrasena FROM administrador` muestra un hash (`$2y$…`), nunca la clave.
- El login sigue funcionando.
- El repositorio no contiene ninguna contraseña en claro.

---

### #2 · Editar productos
**Labels:** `feature`, `prioridad: alta`

**Descripción**
`productos.php` permite crear, consultar y eliminar, pero no editar. La revisión de requerimientos pidió **CU 2.2.4 – Editar Producto**.

**Tareas**
- [ ] Agregar la vista `?vista=editar&id=…` siguiendo el patrón de las otras vistas, con formulario precargado.
- [ ] Botón "Editar" en cada fila de la tabla (hoy las filas no tienen acciones).
- [ ] Incluir el campo `informacionGeneral`, que existe en la tabla pero no aparece en el formulario de "Nuevo producto".
- [ ] `UPDATE` con consulta preparada; mantener `codigoProducto` único.

**Criterios de aceptación**
- Un producto editado se refleja en el listado, el dashboard y las alertas.
- No se permiten precios, IVA o stock negativos.

---

### #3 · Editar categorías
**Labels:** `feature`, `prioridad: media`, `good first issue`

**Descripción**
Implementar **CU 2.3.4 – Editar Categoría**.

**Criterios de aceptación**
- [ ] Se puede cambiar el nombre de una categoría existente.
- [ ] Los productos y proveedores asociados conservan su relación.
- [ ] No se permiten nombres vacíos.

---

### #4 · Editar proveedores
**Labels:** `feature`, `prioridad: media`, `good first issue`

**Descripción**
Implementar **CU 2.4.4 – Editar Proveedor** (nombre de empresa, teléfono, contacto, categoría).

**Criterios de aceptación**
- [ ] Formulario de edición precargado.
- [ ] Validación del teléfono (solo números, longitud razonable).
- [ ] Cambios visibles en el listado.

---

### #5 · Registrar entradas de mercancía (compras a proveedores)
**Labels:** `feature`, `database`, `prioridad: alta`

**Descripción**
El requerimiento **RF-12** exige registrar cada entrada con *fecha, proveedor, producto, cantidad y precio unitario*. El modelo de datos actual **no tiene tabla para esto**, y la revisión (Hallazgo 2) señaló que tampoco hay caso de uso propio. Sin esta función, el stock solo puede bajar.

**Propuesta de modelo**
```sql
CREATE TABLE entradas (
  ID_Entrada     INT AUTO_INCREMENT PRIMARY KEY,
  fechaEntrada   DATE NOT NULL,
  ID_Proveedor   INT NOT NULL,
  ID_Administrador INT NOT NULL,
  FOREIGN KEY (ID_Proveedor) REFERENCES proveedores(ID_Proveedor),
  FOREIGN KEY (ID_Administrador) REFERENCES administrador(ID_Administrador)
);

CREATE TABLE detalle_entradas (
  ID_Detalle     INT AUTO_INCREMENT PRIMARY KEY,
  ID_Entrada     INT NOT NULL,
  ID_Producto    INT NOT NULL,
  cantidad       INT NOT NULL CHECK (cantidad > 0),
  precioUnitario DOUBLE NOT NULL,
  FOREIGN KEY (ID_Entrada)  REFERENCES entradas(ID_Entrada),
  FOREIGN KEY (ID_Producto) REFERENCES productos(ID_Producto)
);
```

**Tareas**
- [ ] Agregar las tablas a `db_9eleven.sql` (y un script de migración).
- [ ] Pantalla "Nueva entrada" dentro del módulo Proveedores.
- [ ] Al guardar, **sumar** la cantidad a `productos.stockActual` dentro de una transacción.
- [ ] Listado/historial de entradas por proveedor.
- [ ] Actualizar diagrama ER y documentación.
- [ ] Mostrar las entradas también en "Movimientos recientes" del dashboard, que hoy solo muestra ventas.
- [ ] En `reportes.php`, el indicador "Costo vendido mes" calcula `precioCompra × cantidad vendida` porque no hay compras reales (lo dice el comentario del código). Cambiarlo a compras reales.

**Criterios de aceptación**
- Una entrada registrada aumenta el stock del producto.
- Si algo falla a mitad de la operación, no queda stock a medias (transacción).

---

### #6 · Registrar ventas (hoy solo se pueden consultar) con descuento de stock
**Labels:** `feature`, `prioridad: alta`

**Descripción**
`ventas.php` es de **solo lectura**: filtra por fecha y muestra encabezados y detalle. **No existe ninguna pantalla para crear una venta**; las que hay vienen del script SQL. Como consecuencia, el stock nunca baja y las alertas solo reaccionan a datos cargados a mano. Falta **CU 2.5.1 – Nueva Venta** (Hallazgo 3 de la revisión).

**Tareas**
- [ ] Botón "Nueva venta" y formulario para agregar varios productos con cantidad.
- [ ] Calcular subtotales y total (considerando `productos.iva`).
- [ ] Dentro de **una transacción** (`beginTransaction`/`commit`): insertar en `ventas`, insertar en `detalle_ventas` y restar de `productos.stockActual`.
- [ ] Rechazar la venta si `cantidad > stockActual` (usar `SELECT … FOR UPDATE` para evitar ventas simultáneas sobre el mismo stock).
- [ ] Guardar en `detalle_ventas.precioUnitario` el precio del momento.
- [ ] Usar el `ID_Administrador` de `$_SESSION['admin_id']`.

**Criterios de aceptación**
- No se puede vender más de lo que hay.
- Tras una venta, dashboard, ventas y alertas muestran datos coherentes.
- Si algo falla a mitad, no queda una venta sin detalle ni stock descontado a medias.

---

### #7 · Mejorar la página de alertas de stock (`alertaStock.php`)
**Labels:** `feature`, `prioridad: baja`, `good first issue`

**Descripción**
La página ya cumple lo esencial de RF-15: lista **todos** los productos con `stockActual <= stockMinimo`, ordenados por cuánto falta, muestra stock actual y mínimo, y marca `CRÍTICO` cuando el stock es 0. Está protegida con `requerirLogin()`, usa `h()` y no recibe parámetros del usuario.

Lo que se puede mejorar:
- El dashboard solo muestra 6 alertas, las ordena por `stockActual` (no por faltante, a diferencia de `alertaStock.php`) y no enlaza a la página completa.
- La página no muestra categoría, cuántas unidades faltan ni a quién pedirle.

**Tareas**
- [ ] Agregar enlace "Ver todas" desde el dashboard y ordenar igual que `alertaStock.php`.
- [ ] Mostrar categoría y faltante (`stockMinimo - stockActual`) en cada alerta.
- [ ] Mostrar los proveedores de la misma categoría como sugerencia de reposición (hoy es la única relación proveedor–producto).
- [ ] Decidir qué pasa con productos de `stockMinimo = 0` (valor por defecto del formulario): hoy entran en la alerta cuando llegan a 0.

**Criterios de aceptación**
- Con los datos de ejemplo aparece *Leche entera* (20 de 30).
- Dashboard y página de alertas muestran el mismo orden.

---

### #8 · Completar los reportes (faltan 3 de 4)
**Labels:** `feature`, `bug`, `prioridad: media`

**Descripción**
RF-14 pide cuatro reportes. `reportes.php` tiene uno completo y dos indicadores del mes:

| Reporte | Estado |
|---------|--------|
| Productos más vendidos | ✅ Top 20 histórico, con filtro por producto y categoría |
| Movimientos de inventario | ❌ Falta (depende de #5, para tener entradas) |
| Productos con stock bajo | ❌ Falta (reutilizar la consulta del dashboard) |
| Valor total del inventario | ❌ Falta (`SUM(stockActual * precioCompra)`) |

**Errores encontrados**
- [ ] La tarjeta **"VENTA MES" aparece dos veces** (copia y pega). La segunda debería ser otro indicador, por ejemplo "Valor del inventario".
- [ ] Los importes se muestran en millones con un decimal: con los datos de ejemplo (ventas de $4.400) sale `0.0 M`. Usar `formatoPesos()`.
- [ ] Sin filtro de fechas: "más vendidos" es siempre histórico. Agregar rango desde/hasta.

**Tareas**
- [ ] Implementar los tres reportes que faltan, con rango de fechas donde aplique.
- [ ] Exportar a CSV (extra).

---

## 🟠 v1.1 — Calidad y seguridad

### #9 · Auditoría de seguridad de las páginas PHP
**Labels:** `security`, `prioridad: alta`

**Descripción**
Revisión hecha sobre todas las páginas PHP del proyecto. El problema de contraseñas va aparte en el #1.

**Lo que ya está bien**
- [x] Consultas con `prepare()` y parámetros (sin concatenar variables en SQL) en las 4 páginas.
- [x] Salida con `h()` en todas las páginas revisadas.
- [x] Las 3 páginas protegidas llaman `requerirLogin()` primero.
- [x] El login da un mensaje de error genérico (no revela si el correo existe).

**Pendiente**
- [x] `categorias.php`, `proveedores.php`, `reportes.php` y `cambiarContrasena.php` también usan `prepare()` y `h()`, y las tres primeras llaman `requerirLogin()` (`cambiarContrasena.php` es pública a propósito).
- [x] `alertaStock.php` cumple la lista (login, `h()`, sin parámetros del usuario). `cerrarSesion.php` destruye la sesión.
- [ ] **CSRF:** ningún formulario lleva token (crear/eliminar productos, etc.). Añadir un token por sesión y validarlo en cada POST.
- [ ] **Sesión:** `session_regenerate_id(true)` tras el login; cookies con `HttpOnly` y `SameSite`; `cerrarSesion.php` ya vacía `$_SESSION` y llama `session_destroy()`, pero falta borrar la cookie de sesión con `setcookie()`; además cierra la sesión con un simple enlace GET (otra web podría cerrarla), por lo que conviene pasarlo a POST con token.
- [ ] **Fuerza bruta:** limitar intentos fallidos de login (por IP o por correo).
- [ ] **Errores:** `config.php` imprime el mensaje de la excepción de PDO; registrarlo en un log y mostrar un mensaje genérico. Lo mismo para excepciones sin capturar (ver #12).
- [ ] **Post/Redirect/Get:** tras un POST exitoso hacer `header('Location: …')`; hoy, recargar la página reenvía el formulario.

---

### #10 · Recuperar una contraseña olvidada (hoy solo se puede cambiar conociendo la actual)
**Labels:** `feature`, `security`, `prioridad: media`

**Descripción**
`cambiarContrasena.php` pide email, contraseña actual y la nueva, y guarda con `password_hash`. Eso funciona, pero es **cambiar**, no **recuperar**: RF-03, CU 1.2 y la pantalla GUI 1.2 piden que quien olvidó su clave la restablezca con su correo. Hoy, si el administrador la olvida, solo se arregla editando la base a mano.

Además la página es pública y no limita intentos: sirve para probar contraseñas, igual que el login.

**Tareas**
- [ ] Enlace "Olvidé mi contraseña" en el login que pida solo el correo.
- [ ] Tabla `password_resets` (token hasheado, expiración, usado).
- [ ] Enviar el enlace por correo con PHPMailer + SMTP configurable (XAMPP no trae servidor de correo). Para desarrollo local, escribir el enlace en un log.
- [ ] Página para definir la nueva contraseña con el token (vigencia corta y un solo uso).
- [ ] Responder igual exista o no el correo.
- [ ] Limitar intentos y agregar token CSRF en `cambiarContrasena.php`.
- [ ] Subir el mínimo de 6 a 8 caracteres.

---

### #11 · Validaciones de formularios (cliente y servidor)
**Labels:** `refactor`, `prioridad: media`

**Descripción**
Unificar las validaciones de todos los módulos.

**Reglas mínimas**
- [ ] Precios, IVA y stock no negativos.
- [ ] Campos obligatorios no vacíos ni solo espacios.
- [ ] Código de producto único con mensaje claro. Hoy `productos.php` responde "Ya existe un producto con ese código" ante **cualquier** error `23000`, incluida una categoría inexistente (clave foránea): distinguir por `errorInfo[1]` (1062 duplicado, 1452 clave foránea).
- [ ] Rechazar `precio_venta < precio_compra` o avisar al usuario.
- [ ] `categorias.php` permite **nombres duplicados**: agregar `UNIQUE` a `nombreCategoria` y capturar el error. Tanto categorías como proveedores hacen el `INSERT` sin `try/catch`.
- [ ] `proveedores.php` guarda contacto y teléfono vacíos como cadena vacía en vez de `NULL`, por lo que el "—" de la tabla nunca aparece. Validar el teléfono (solo dígitos).
- [ ] Los IDs de proveedores (`V001`) y de ventas (`V0001`) usan la misma letra; usar un prefijo distinto para proveedores (por ejemplo `PR`).
- [ ] Validar siempre en servidor aunque exista validación en el navegador.

---

### #12 · Eliminar registros sin errores fatales ni borrados accidentales
**Labels:** `bug`, `database`, `prioridad: alta`

**Descripción**
Problemas confirmados en `productos.php`:

1. **Error sin capturar.** El `DELETE` no está dentro de `try/catch`. Si el producto ya tiene ventas (por ejemplo *Agua 600ml* o *Papas fritas* de los datos de ejemplo), la clave foránea de `detalle_ventas` lo impide y PHP lanza una `PDOException` sin capturar: el usuario ve una página de error técnico.
2. **Se borra por nombre.** `DELETE … WHERE nombreProducto = ?` elimina **todos** los productos con ese nombre exacto, y los nombres no son únicos. Además hay que teclear el nombre completo.
3. **Categorías y proveedores** repiten el borrado por nombre (eliminan a todos los homónimos). Al borrar una categoría no hay error, pero sus productos y proveedores quedan **sin categoría en silencio** (`ON DELETE SET NULL`), sin avisar cuántos se afectan.

**Tareas**
- [ ] Envolver el `DELETE` en `try/catch` y mostrar "No se puede eliminar: el producto tiene ventas registradas".
- [ ] Borrar por `ID_Producto`, con un botón "Eliminar" en cada fila de la tabla y confirmación.
- [ ] Aplicar lo mismo a `categorias.php` y `proveedores.php`: borrar por ID, con botón por fila.
- [ ] En categorías, avisar cuántos productos quedarán sin categoría antes de confirmar (o bloquear si tiene productos).
- [ ] Opcional: borrado lógico (columna `activo`) para conservar el historial.

**Criterios de aceptación**
- Intentar borrar un producto con ventas muestra un mensaje claro.
- Nunca se elimina más de un producto por acción.

---

### #13 · Actualizar el diagrama ER del documento funcional
**Labels:** `docs`, `database`, `good first issue`

**Descripción**
El script SQL tiene en su cabecera la lista de cambios hechos al modelo original, pero el PDF no se actualizó. Diferencias:
- El ER dibuja `Alerta_Stock` y `Reportes` como tablas; el SQL las eliminó (se calculan con consultas).
- El ER pone `ID_Producto` dentro de `Ventas`; el SQL usa `ventas` + `detalle_ventas`.
- `productos.stockMinimo` se agregó para las alertas.
- Nombre real de la columna: `contrasena` (sin ñ).
- No hay entradas de mercancía en ninguno (ver #5).

**Tareas**
- [ ] Redibujar el ER con las 6 tablas actuales (y las de #5 cuando existan).
- [ ] Agregarlo a `docs/` y enlazarlo desde el README.

---

### #17 · Configuración por entorno (sin editar `config.php` en cada máquina)
**Labels:** `devops`, `security`, `prioridad: media`, `good first issue`

**Descripción**
`config.php` trae `root` y contraseña vacía escritos en el código. Sirve para XAMPP, pero en producción obliga a editar un archivo versionado y arriesga subir credenciales reales.

**Tareas**
- [ ] Leer `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` de un archivo `.env` o de variables de entorno, con los valores actuales como respaldo.
- [ ] Agregar `.env` al `.gitignore` y un `.env.example`.
- [ ] Actualizar la sección de despliegue del README.

---

### #18 · Zona horaria de Colombia
**Labels:** `bug`, `database`, `prioridad: media`

**Descripción**
La fecha "de hoy" se calcula en dos lugares distintos: `ventas.php` usa `date('Y-m-d')` (zona de PHP, según `php.ini`) y `dashboard.php` usa `CURDATE()` (zona de MariaDB). `config.php` no fija ninguna, y el script SQL declara `+00:00`. Después de las 7 p. m. en Colombia (UTC-5) las dos pantallas pueden mostrar **días distintos**, y una venta nueva podría guardarse con la fecha de mañana.

**Tareas**
- [ ] `date_default_timezone_set('America/Bogota');` en `config.php`.
- [ ] Fijar la zona de la conexión: `SET time_zone = '-05:00'` al conectar.
- [ ] Usar una sola fuente para "hoy" (preferible PHP, pasando la fecha como parámetro).
- [ ] Probar el dashboard y ventas pasadas las 7 p. m.

---

## 🟢 v2.0 — Mejoras

### #14 · Paginación y buscador en los listados
**Labels:** `feature`, `prioridad: baja`

Productos, ventas y proveedores deben paginar (ej. 20 por página) y filtrar por nombre/código/categoría.

---

### #15 · Dockerizar el proyecto
**Labels:** `devops`, `prioridad: baja`

Agregar `Dockerfile` + `docker-compose.yml` (PHP 8.3-apache + MariaDB 10.11) con la BD importada automáticamente. Así un colaborador nuevo levanta todo con `docker compose up`.

---

### #16 · Pruebas y CI
**Labels:** `devops`, `prioridad: baja`

- [ ] GitHub Action que ejecute `php -l` (revisión de sintaxis) en cada PR.
- [ ] Pruebas con PHPUnit para la lógica de stock y totales.
- [ ] Plantillas de Issue y Pull Request en `.github/`.

---

## 📝 Plantilla para nuevos issues

```markdown
**Contexto**
¿Qué módulo y qué pasa hoy?

**Qué se espera**
Resultado deseado.

**Pasos para reproducir** (si es un bug)
1.
2.

**Criterios de aceptación**
- [ ]
- [ ]
```
