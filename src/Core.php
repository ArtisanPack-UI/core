<?php

/**
 * Entry-point service object for the ArtisanPack UI Core package.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

namespace ArtisanPackUI\Core;

/**
 * Container-resolvable handle for the ArtisanPack UI Core package.
 *
 * Resolved from the service container under the `core` alias by
 * {@see CoreServiceProvider}. The class is intentionally a thin marker so that
 * applications, sibling packages, and the {@see Facades\Core} facade have a
 * stable target to bind against while the package itself exposes its
 * functionality through dedicated services
 * ( {@see Config\ConfigurationManager}, {@see Logging\LoggerFactory},
 * {@see View\BladeDirectiveRegistrar}, {@see Diagnostics\DiagnosticRunner},
 * {@see Compatibility\CompatibilityChecker}, {@see Updates\UpdateChecker} ).
 *
 * @package    ArtisanPack_UI
 * @subpackage Core
 *
 * @since      1.0.0
 */
class Core
{
}
