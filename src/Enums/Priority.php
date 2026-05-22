<?php

/**
 * Enum of priority levels.
 *
 * @since      1.2.0
 *
 * @package    ArtisanPackUI\Core\Enums
 */

namespace ArtisanPackUI\Core\Enums;

/**
 * Represents priority levels used for ordering and triage across the
 * ArtisanPack UI ecosystem.
 *
 * @since 1.2.0
 *
 * @package ArtisanPackUI\Core\Enums
 */
enum Priority: string
{
	case LOW      = 'low';
	case MEDIUM   = 'medium';
	case HIGH     = 'high';
	case CRITICAL = 'critical';
}
