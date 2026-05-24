<?php

/**
 * Enum of log severity levels.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Enums
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Enums;

/**
 * Represents the standard log severity levels used across the ArtisanPack UI
 * ecosystem.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Enums
 *
 * @since      1.2.0
 */
enum LogLevel: string
{
	case DEBUG    = 'debug';
	case INFO     = 'info';
	case WARNING  = 'warning';
	case ERROR    = 'error';
	case CRITICAL = 'critical';
}
