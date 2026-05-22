<?php

/**
 * Enum of log severity levels.
 *
 * @since      1.2.0
 *
 * @package    ArtisanPackUI\Core\Enums
 */

namespace ArtisanPackUI\Core\Enums;

/**
 * Represents the standard log severity levels used across the ArtisanPack UI
 * ecosystem.
 *
 * @since 1.2.0
 *
 * @package ArtisanPackUI\Core\Enums
 */
enum LogLevel: string
{
	case DEBUG    = 'debug';
	case INFO     = 'info';
	case WARNING  = 'warning';
	case ERROR    = 'error';
	case CRITICAL = 'critical';
}
