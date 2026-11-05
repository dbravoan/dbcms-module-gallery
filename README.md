# dbravoan/dbcms-module-gallery 1.0.0

## Qué hace

`dbravoan/dbcms-module-gallery` aporta galerías de imágenes filtrables, optimización WebP y lightbox. Es pageable (`is_pageable: true`) y registra morfismo `gallery`, bloque de álbum, búsqueda y semántica pública.

## Requisitos y dependencias

- `dbravoan/dbcms-core` `^1.0`
- Identificador: `gallery`

## Instalación y activación

Instala y activa `gallery`. Configura slugs localizados. Gestiona álbumes en el panel con permiso `manage:galleries` (migración del módulo).

## Configuración

Ajustes del módulo (panel; claves por nombre):

- `slug_default`
- `slug_{locale}`

## Rutas

**Admin** (prefijo del panel + `galleries`, permiso `manage:galleries`):
- `GET /` → `galleries.index`
- `GET /create` → `galleries.create`
- `POST /` → `galleries.store`
- `GET /{gallery}/edit` → `galleries.edit`
- `PUT /{gallery}` → `galleries.update`
- `DELETE /{gallery}` → `galleries.destroy`

**Público** (prefijo de módulo localizado `{galleryPrefix}`):
- `gallery.index` — listado
- `gallery.show` — detalle por slug
Variantes con `{market}/{locale}`, `{locale}` y sin prefijo de mercado/locale.

## Comandos Artisan

Ninguno.

## Permisos

- `manage:galleries` (migración `Database/Migrations/2026_10_10_230000_add_gallery_management_permission.php`)
- Uso de `manage:media` en flujos editoriales que tocan medios

## Privacidad y datos

Contenido editorial de galerías/medios. No se encontró comando `*:privacy` propio del módulo en el código. Declarar tratamientos del núcleo/medios según el contenido publicado. Gap: no hay política `legal.processing` específica documentada en el código del paquete más allá del contenido público.

## Enlaces

- [Extensiones](../../../docs/EXTENSIONS.md)
- [Núcleo y extensiones](../../../specs/CORE_AND_EXTENSIONS.md)
- [README del núcleo](../../../packages/core/README.md)
- [README del monorepo](../../../README.md)
