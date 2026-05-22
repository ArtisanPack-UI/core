<?php

/**
 * Enum of generic record statuses.
 *
 * @since      1.2.0
 *
 * @package    ArtisanPackUI\Core\Enums
 */

namespace ArtisanPackUI\Core\Enums;

/**
 * Represents generic lifecycle statuses for records across the ArtisanPack UI
 * ecosystem.
 *
 * @since 1.2.0
 *
 * @package ArtisanPackUI\Core\Enums
 */
enum Status: string
{
	case PENDING  = 'pending';
	case ACTIVE   = 'active';
	case INACTIVE = 'inactive';
	case DELETED  = 'deleted';
}
