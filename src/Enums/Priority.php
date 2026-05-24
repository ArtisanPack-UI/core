<?php

/**
 * Enum of priority levels.
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
 * Represents priority levels used for ordering and triage across the
 * ArtisanPack UI ecosystem.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Enums
 *
 * @since      1.2.0
 */
enum Priority: string
{
	case LOW      = 'low';
	case MEDIUM   = 'medium';
	case HIGH     = 'high';
	case CRITICAL = 'critical';
}
