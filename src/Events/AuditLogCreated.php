<?php

/**
 * Event dispatched whenever an audit log entry is recorded.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Events
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Events;

// PHP requires the lowercase __construct magic method; the
// MagicMethodNotUppercase sniff misfires on the constructor here.
// phpcs:disable ArtisanPackUI.TypeHints.TypeDeclaration.MagicMethodNotUppercase

/**
 * Notifies the application that an ArtisanPack UI package has recorded an
 * audit entry.
 *
 * Applications may listen for this event to persist audit records to a
 * database, forward them to an external aggregation service ( such as
 * Elasticsearch ), or trigger downstream notifications.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Events
 *
 * @since      1.2.0
 */
class AuditLogCreated
{
	/**
	 * Create a new audit log created event.
	 *
	 * The supplied array contains the package short name, action identifier,
	 * caller-supplied data, actor id, request ip, and timestamp.
	 *
	 * @since 1.2.0
	 *
	 * @param  array<string, mixed>  $data  The structured audit payload.
	 */
	public function __construct( public readonly array $data )
	{
	}
}
