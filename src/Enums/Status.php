<?php

/**
 * Enum of generic record statuses.
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
 * Represents generic lifecycle statuses for records across the ArtisanPack UI
 * ecosystem.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Enums
 *
 * @since      1.2.0
 */
enum Status: string
{
	case PENDING  = 'pending';
	case ACTIVE   = 'active';
	case INACTIVE = 'inactive';
	case DELETED  = 'deleted';
}
