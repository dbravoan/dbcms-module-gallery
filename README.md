# Gallery

Gestiona galerías e imágenes localizadas y sus bloques. Los slugs, enlaces, traducciones y lectores públicos respetan idioma, mercado, publicación y activación del módulo.

Este repositorio es la fuente de desarrollo y distribución de
`dbravoan/dbcms-module-gallery`. La línea `0.1` requiere core `^0.1`, PHP 8.4
y Laravel 13.

## Instalación y ciclo de vida

En una aplicación dbCMS compatible:

```bash
composer require dbravoan/dbcms-module-gallery:~0.0.1
```

Instala y activa el módulo desde el catálogo del panel con los permisos
correspondientes. Composer descarga código; la instalación explícita aplica sus
migraciones y registra su estado. Desactivar conserva los datos y bloquea las
contribuciones del módulo. Las dependencias activas impiden retirarlo de forma
incoherente.

Para una aplicación nueva también puedes seleccionarlo con
`dbcms new mi-web --modules=gallery`. La configuración inicial del sitio se completa
en `/install`.

## Desarrollo y mantenimiento

[Pruebas, compatibilidad y publicación](docs/MAINTENANCE.md). Las reglas públicas
de [arquitectura y extensiones](https://github.com/dbravoan/dbcms-core/tree/main/docs)
pertenecen a core. Este paquete no necesita el antiguo monorepo ni un starter de
dbCMS para comprobarse.
