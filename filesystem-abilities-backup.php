	// =========================================================================
	// FILESYSTEM ABILITIES
	// =========================================================================
	// These abilities provide FTP-like file operations via MCP.
	// All operations are restricted to the WordPress installation directory.
	// Core files (wp-includes/, wp-admin/) cannot be modified.
	// =========================================================================

	// =========================================================================
	// FILESYSTEM - Read File
	// =========================================================================
	wp_register_ability(
		'filesystem/read-file',
		array(
			'label'               => 'Read File',
			'description'         => '[FILESYSTEM] Reads file contents. Restricted to WordPress directory. Explain to user what file and why before using.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'path' => array(
						'type'        => 'string',
						'description' => 'File path (absolute or relative to WordPress root). Examples: ".htaccess", "wp-config.php", "wp-content/themes/mytheme/style.css"',
					),
				),
				'required'             => array( 'path' ),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'  => array( 'type' => 'boolean' ),
					'content'  => array( 'type' => 'string' ),
					'path'     => array( 'type' => 'string' ),
					'size'     => array( 'type' => 'integer' ),
					'modified' => array( 'type' => 'string' ),
					'message'  => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => function ( array $input ): array {
				$path = $input['path'] ?? '';

				if ( empty( $path ) ) {
					return array(
						'success' => false,
						'message' => 'Path is required.',
					);
				}

				// Resolve path relative to ABSPATH if not absolute.
				if ( strpos( $path, '/' ) !== 0 ) {
					$full_path = ABSPATH . $path;
				} else {
					$full_path = $path;
				}

				$full_path = realpath( $full_path );

				if ( false === $full_path ) {
					return array(
						'success' => false,
						'message' => 'File not found: ' . $input['path'],
					);
				}

				// Security: ensure file is within WordPress directory or its parent.
				$allowed_base = dirname( ABSPATH );
				if ( strpos( $full_path, $allowed_base ) !== 0 ) {
					return array(
						'success' => false,
						'message' => 'Access denied. File must be within the WordPress installation directory.',
					);
				}

				if ( ! is_file( $full_path ) ) {
					return array(
						'success' => false,
						'message' => 'Path is not a file: ' . $input['path'],
					);
				}

				if ( ! is_readable( $full_path ) ) {
					return array(
						'success' => false,
						'message' => 'File is not readable: ' . $input['path'],
					);
				}

				$content = file_get_contents( $full_path );

				if ( false === $content ) {
					return array(
						'success' => false,
						'message' => 'Failed to read file: ' . $input['path'],
					);
				}

				return array(
					'success'  => true,
					'content'  => $content,
					'path'     => $full_path,
					'size'     => filesize( $full_path ),
					'modified' => gmdate( 'Y-m-d H:i:s', filemtime( $full_path ) ),
				);
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'manage_options' );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => true,
					'destructive' => false,
					'idempotent'  => true,
				),
			),
		)
	);

	// =========================================================================
	// FILESYSTEM - Write File
	// =========================================================================
	wp_register_ability(
		'filesystem/write-file',
		array(
			'label'               => 'Write File',
			'description'         => '[FILESYSTEM] Creates/overwrites file. DESTRUCTIVE - explain what file and content before using. Cannot modify core files.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'path'    => array(
						'type'        => 'string',
						'description' => 'File path (absolute or relative to WordPress root).',
					),
					'content' => array(
						'type'        => 'string',
						'description' => 'Content to write to the file.',
					),
					'backup'  => array(
						'type'        => 'boolean',
						'default'     => true,
						'description' => 'Create a backup before overwriting (default: true).',
					),
				),
				'required'             => array( 'path', 'content' ),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'     => array( 'type' => 'boolean' ),
					'message'     => array( 'type' => 'string' ),
					'path'        => array( 'type' => 'string' ),
					'backup_path' => array( 'type' => 'string' ),
					'bytes'       => array( 'type' => 'integer' ),
				),
			),
			'execute_callback'    => function ( array $input ): array {
				$path    = $input['path'] ?? '';
				$content = $input['content'] ?? '';
				$backup  = $input['backup'] ?? true;

				if ( empty( $path ) ) {
					return array(
						'success' => false,
						'message' => 'Path is required.',
					);
				}

				// Resolve path relative to ABSPATH if not absolute.
				if ( strpos( $path, '/' ) !== 0 ) {
					$full_path = ABSPATH . $path;
				} else {
					$full_path = $path;
				}

				// For new files, check the directory exists.
				$dir = dirname( $full_path );
				if ( ! is_dir( $dir ) ) {
					return array(
						'success' => false,
						'message' => 'Directory does not exist: ' . $dir,
					);
				}

				$real_dir = realpath( $dir );

				// Security: ensure within WordPress directory.
				$allowed_base = dirname( ABSPATH );
				if ( strpos( $real_dir, $allowed_base ) !== 0 ) {
					return array(
						'success' => false,
						'message' => 'Access denied. File must be within the WordPress installation directory.',
					);
				}

				// Security: block core file modifications.
				$full_path_normalized = $real_dir . '/' . basename( $full_path );
				if ( strpos( $full_path_normalized, ABSPATH . 'wp-includes/' ) === 0 ||
					strpos( $full_path_normalized, ABSPATH . 'wp-admin/' ) === 0 ) {
					return array(
						'success' => false,
						'message' => 'Cannot modify WordPress core files in wp-includes or wp-admin.',
					);
				}

				$backup_path = null;

				// Create backup if file exists.
				if ( $backup && file_exists( $full_path_normalized ) ) {
					$backup_path = $full_path_normalized . '.bak.' . gmdate( 'YmdHis' );
					if ( ! copy( $full_path_normalized, $backup_path ) ) {
						return array(
							'success' => false,
							'message' => 'Failed to create backup.',
						);
					}
				}

				$bytes = file_put_contents( $full_path_normalized, $content );

				if ( false === $bytes ) {
					return array(
						'success' => false,
						'message' => 'Failed to write file: ' . $path,
					);
				}

				$result = array(
					'success' => true,
					'message' => 'File written successfully.',
					'path'    => $full_path_normalized,
					'bytes'   => $bytes,
				);

				if ( $backup_path ) {
					$result['backup_path'] = $backup_path;
				}

				return $result;
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'manage_options' );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => false,
					'destructive' => true,
					'idempotent'  => false,
				),
			),
		)
	);

	// =========================================================================
	// FILESYSTEM - Append to File
	// =========================================================================
	wp_register_ability(
		'filesystem/append-file',
		array(
			'label'               => 'Append to File',
			'description'         => '[FILESYSTEM] Appends to file (e.g., .htaccess rules). Explain what you are adding before using.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'path'    => array(
						'type'        => 'string',
						'description' => 'File path (absolute or relative to WordPress root).',
					),
					'content' => array(
						'type'        => 'string',
						'description' => 'Content to append to the file.',
					),
					'prepend' => array(
						'type'        => 'boolean',
						'default'     => false,
						'description' => 'If true, add content to the beginning instead of the end.',
					),
				),
				'required'             => array( 'path', 'content' ),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'message' => array( 'type' => 'string' ),
					'path'    => array( 'type' => 'string' ),
					'bytes'   => array( 'type' => 'integer' ),
				),
			),
			'execute_callback'    => function ( array $input ): array {
				$path    = $input['path'] ?? '';
				$content = $input['content'] ?? '';
				$prepend = $input['prepend'] ?? false;

				if ( empty( $path ) ) {
					return array(
						'success' => false,
						'message' => 'Path is required.',
					);
				}

				// Resolve path.
				if ( strpos( $path, '/' ) !== 0 ) {
					$full_path = ABSPATH . $path;
				} else {
					$full_path = $path;
				}

				$full_path = realpath( $full_path );

				if ( false === $full_path || ! file_exists( $full_path ) ) {
					return array(
						'success' => false,
						'message' => 'File not found: ' . $input['path'],
					);
				}

				// Security check.
				$allowed_base = dirname( ABSPATH );
				if ( strpos( $full_path, $allowed_base ) !== 0 ) {
					return array(
						'success' => false,
						'message' => 'Access denied.',
					);
				}

				// Block core files.
				if ( strpos( $full_path, ABSPATH . 'wp-includes/' ) === 0 ||
					strpos( $full_path, ABSPATH . 'wp-admin/' ) === 0 ) {
					return array(
						'success' => false,
						'message' => 'Cannot modify WordPress core files.',
					);
				}

				if ( $prepend ) {
					$existing = file_get_contents( $full_path );
					$bytes    = file_put_contents( $full_path, $content . $existing );
				} else {
					$bytes = file_put_contents( $full_path, $content, FILE_APPEND );
				}

				if ( false === $bytes ) {
					return array(
						'success' => false,
						'message' => 'Failed to append to file.',
					);
				}

				return array(
					'success' => true,
					'message' => 'Content appended successfully.',
					'path'    => $full_path,
					'bytes'   => $bytes,
				);
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'manage_options' );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => false,
				),
			),
		)
	);

	// =========================================================================
	// FILESYSTEM - List Directory
	// =========================================================================
	wp_register_ability(
		'filesystem/list-directory',
		array(
			'label'               => 'List Directory',
			'description'         => '[FILESYSTEM] Lists directory contents. Safe read-only operation.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'path'      => array(
						'type'        => 'string',
						'default'     => '.',
						'description' => 'Directory path (absolute or relative to WordPress root). Default is WordPress root.',
					),
					'recursive' => array(
						'type'        => 'boolean',
						'default'     => false,
						'description' => 'Include subdirectories recursively (max 2 levels deep).',
					),
					'pattern'   => array(
						'type'        => 'string',
						'description' => 'Filter files by pattern (e.g., "*.php", "*.js").',
					),
				),
				'required'             => array(),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'path'    => array( 'type' => 'string' ),
					'items'   => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'name'     => array( 'type' => 'string' ),
								'type'     => array( 'type' => 'string' ),
								'size'     => array( 'type' => 'integer' ),
								'modified' => array( 'type' => 'string' ),
								'path'     => array( 'type' => 'string' ),
							),
						),
					),
					'message' => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => function ( array $input ): array {
				$path      = $input['path'] ?? '.';
				$recursive = $input['recursive'] ?? false;
				$pattern   = $input['pattern'] ?? null;

				// Resolve path.
				if ( '.' === $path || empty( $path ) ) {
					$full_path = ABSPATH;
				} elseif ( strpos( $path, '/' ) !== 0 ) {
					$full_path = ABSPATH . $path;
				} else {
					$full_path = $path;
				}

				$full_path = realpath( $full_path );

				if ( false === $full_path ) {
					return array(
						'success' => false,
						'message' => 'Directory not found: ' . $input['path'],
					);
				}

				// Security check.
				$allowed_base = dirname( ABSPATH );
				if ( strpos( $full_path, $allowed_base ) !== 0 ) {
					return array(
						'success' => false,
						'message' => 'Access denied.',
					);
				}

				if ( ! is_dir( $full_path ) ) {
					return array(
						'success' => false,
						'message' => 'Path is not a directory.',
					);
				}

				$items = array();

				$list_dir = function ( $dir, $depth = 0 ) use ( &$list_dir, &$items, $recursive, $pattern ) {
					if ( $depth > 2 ) {
						return;
					}

					$files = scandir( $dir );
					foreach ( $files as $file ) {
						if ( '.' === $file || '..' === $file ) {
							continue;
						}

						$file_path = $dir . '/' . $file;

						// Pattern matching.
						if ( $pattern && ! fnmatch( $pattern, $file ) && is_file( $file_path ) ) {
							continue;
						}

						$is_dir = is_dir( $file_path );
						$items[] = array(
							'name'     => $file,
							'type'     => $is_dir ? 'directory' : 'file',
							'size'     => $is_dir ? 0 : filesize( $file_path ),
							'modified' => gmdate( 'Y-m-d H:i:s', filemtime( $file_path ) ),
							'path'     => $file_path,
						);

						if ( $recursive && $is_dir ) {
							$list_dir( $file_path, $depth + 1 );
						}
					}
				};

				$list_dir( $full_path );

				return array(
					'success' => true,
					'path'    => $full_path,
					'items'   => $items,
				);
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'manage_options' );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => true,
					'destructive' => false,
					'idempotent'  => true,
				),
			),
		)
	);

	// =========================================================================
	// FILESYSTEM - Delete File
	// =========================================================================
	wp_register_ability(
		'filesystem/delete-file',
		array(
			'label'               => 'Delete File',
			'description'         => '[FILESYSTEM] Deletes file. DESTRUCTIVE - explain what and why before using. Creates backup by default.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'path'   => array(
						'type'        => 'string',
						'description' => 'File path to delete.',
					),
					'backup' => array(
						'type'        => 'boolean',
						'default'     => true,
						'description' => 'Create a .deleted backup before deleting.',
					),
				),
				'required'             => array( 'path' ),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'     => array( 'type' => 'boolean' ),
					'message'     => array( 'type' => 'string' ),
					'backup_path' => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => function ( array $input ): array {
				$path   = $input['path'] ?? '';
				$backup = $input['backup'] ?? true;

				if ( empty( $path ) ) {
					return array(
						'success' => false,
						'message' => 'Path is required.',
					);
				}

				// Resolve path.
				if ( strpos( $path, '/' ) !== 0 ) {
					$full_path = ABSPATH . $path;
				} else {
					$full_path = $path;
				}

				$full_path = realpath( $full_path );

				if ( false === $full_path || ! file_exists( $full_path ) ) {
					return array(
						'success' => false,
						'message' => 'File not found.',
					);
				}

				// Security check.
				$allowed_base = dirname( ABSPATH );
				if ( strpos( $full_path, $allowed_base ) !== 0 ) {
					return array(
						'success' => false,
						'message' => 'Access denied.',
					);
				}

				// Block directories.
				if ( is_dir( $full_path ) ) {
					return array(
						'success' => false,
						'message' => 'Cannot delete directories. Use filesystem/delete-directory instead.',
					);
				}

				// Block core files.
				if ( strpos( $full_path, ABSPATH . 'wp-includes/' ) === 0 ||
					strpos( $full_path, ABSPATH . 'wp-admin/' ) === 0 ) {
					return array(
						'success' => false,
						'message' => 'Cannot delete WordPress core files.',
					);
				}

				// Block critical files.
				$critical = array( 'wp-config.php', '.htaccess', 'index.php' );
				if ( in_array( basename( $full_path ), $critical, true ) && dirname( $full_path ) === rtrim( ABSPATH, '/' ) ) {
					return array(
						'success' => false,
						'message' => 'Cannot delete critical WordPress files. Use write-file to modify instead.',
					);
				}

				$backup_path = null;
				if ( $backup ) {
					$backup_path = $full_path . '.deleted.' . gmdate( 'YmdHis' );
					if ( ! copy( $full_path, $backup_path ) ) {
						return array(
							'success' => false,
							'message' => 'Failed to create backup.',
						);
					}
				}

				if ( ! unlink( $full_path ) ) {
					return array(
						'success' => false,
						'message' => 'Failed to delete file.',
					);
				}

				$result = array(
					'success' => true,
					'message' => 'File deleted successfully.',
				);

				if ( $backup_path ) {
					$result['backup_path'] = $backup_path;
				}

				return $result;
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'manage_options' );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => false,
					'destructive' => true,
					'idempotent'  => false,
				),
			),
		)
	);

	// =========================================================================
	// FILESYSTEM - File Info
	// =========================================================================
	wp_register_ability(
		'filesystem/file-info',
		array(
			'label'               => 'Get File Info',
			'description'         => '[FILESYSTEM] Gets file/directory metadata. Safe read-only operation.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'path' => array(
						'type'        => 'string',
						'description' => 'File or directory path.',
					),
				),
				'required'             => array( 'path' ),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'     => array( 'type' => 'boolean' ),
					'path'        => array( 'type' => 'string' ),
					'type'        => array( 'type' => 'string' ),
					'size'        => array( 'type' => 'integer' ),
					'permissions' => array( 'type' => 'string' ),
					'owner'       => array( 'type' => 'string' ),
					'group'       => array( 'type' => 'string' ),
					'created'     => array( 'type' => 'string' ),
					'modified'    => array( 'type' => 'string' ),
					'accessed'    => array( 'type' => 'string' ),
					'readable'    => array( 'type' => 'boolean' ),
					'writable'    => array( 'type' => 'boolean' ),
					'message'     => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => function ( array $input ): array {
				$path = $input['path'] ?? '';

				if ( empty( $path ) ) {
					return array(
						'success' => false,
						'message' => 'Path is required.',
					);
				}

				// Resolve path.
				if ( strpos( $path, '/' ) !== 0 ) {
					$full_path = ABSPATH . $path;
				} else {
					$full_path = $path;
				}

				$full_path = realpath( $full_path );

				if ( false === $full_path ) {
					return array(
						'success' => false,
						'message' => 'Path not found.',
					);
				}

				// Security check.
				$allowed_base = dirname( ABSPATH );
				if ( strpos( $full_path, $allowed_base ) !== 0 ) {
					return array(
						'success' => false,
						'message' => 'Access denied.',
					);
				}

				$stat = stat( $full_path );

				return array(
					'success'     => true,
					'path'        => $full_path,
					'type'        => is_dir( $full_path ) ? 'directory' : 'file',
					'size'        => $stat['size'],
					'permissions' => substr( sprintf( '%o', fileperms( $full_path ) ), -4 ),
					'owner'       => function_exists( 'posix_getpwuid' ) ? posix_getpwuid( $stat['uid'] )['name'] ?? $stat['uid'] : $stat['uid'],
					'group'       => function_exists( 'posix_getgrgid' ) ? posix_getgrgid( $stat['gid'] )['name'] ?? $stat['gid'] : $stat['gid'],
					'created'     => gmdate( 'Y-m-d H:i:s', $stat['ctime'] ),
					'modified'    => gmdate( 'Y-m-d H:i:s', $stat['mtime'] ),
					'accessed'    => gmdate( 'Y-m-d H:i:s', $stat['atime'] ),
					'readable'    => is_readable( $full_path ),
					'writable'    => is_writable( $full_path ),
				);
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'manage_options' );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => true,
					'destructive' => false,
					'idempotent'  => true,
				),
			),
		)
	);

	// =========================================================================
	// FILESYSTEM - Create Directory
	// =========================================================================
	wp_register_ability(
		'filesystem/create-directory',
		array(
			'label'               => 'Create Directory',
			'description'         => '[FILESYSTEM] Creates directory. Explain purpose before using.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'path'        => array(
						'type'        => 'string',
						'description' => 'Directory path to create.',
					),
					'permissions' => array(
						'type'        => 'string',
						'default'     => '0755',
						'description' => 'Permissions for the new directory (default: 0755).',
					),
					'recursive'   => array(
						'type'        => 'boolean',
						'default'     => true,
						'description' => 'Create parent directories if they do not exist.',
					),
				),
				'required'             => array( 'path' ),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'message' => array( 'type' => 'string' ),
					'path'    => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => function ( array $input ): array {
				$path        = $input['path'] ?? '';
				$permissions = octdec( $input['permissions'] ?? '0755' );
				$recursive   = $input['recursive'] ?? true;

				if ( empty( $path ) ) {
					return array(
						'success' => false,
						'message' => 'Path is required.',
					);
				}

				// Resolve path.
				if ( strpos( $path, '/' ) !== 0 ) {
					$full_path = ABSPATH . $path;
				} else {
					$full_path = $path;
				}

				// Check parent directory.
				$parent = dirname( $full_path );
				$real_parent = realpath( $parent );

				// If parent doesn't exist and not recursive, fail.
				if ( false === $real_parent && ! $recursive ) {
					return array(
						'success' => false,
						'message' => 'Parent directory does not exist.',
					);
				}

				// Security: check parent is within allowed base.
				$allowed_base = dirname( ABSPATH );
				if ( $real_parent && strpos( $real_parent, $allowed_base ) !== 0 ) {
					return array(
						'success' => false,
						'message' => 'Access denied.',
					);
				}

				if ( file_exists( $full_path ) ) {
					return array(
						'success' => false,
						'message' => 'Path already exists.',
					);
				}

				if ( ! mkdir( $full_path, $permissions, $recursive ) ) {
					return array(
						'success' => false,
						'message' => 'Failed to create directory.',
					);
				}

				return array(
					'success' => true,
					'message' => 'Directory created successfully.',
					'path'    => realpath( $full_path ),
				);
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'manage_options' );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => false,
				),
			),
		)
	);

	// =========================================================================
	// FILESYSTEM - Copy File
	// =========================================================================
	wp_register_ability(
		'filesystem/copy-file',
		array(
			'label'               => 'Copy File',
			'description'         => '[FILESYSTEM] Copies file. Explain source, destination, and purpose before using.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'source'    => array(
						'type'        => 'string',
						'description' => 'Source file path.',
					),
					'dest'      => array(
						'type'        => 'string',
						'description' => 'Destination file path.',
					),
					'overwrite' => array(
						'type'        => 'boolean',
						'default'     => false,
						'description' => 'Overwrite destination if it exists.',
					),
				),
				'required'             => array( 'source', 'dest' ),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'message' => array( 'type' => 'string' ),
					'source'  => array( 'type' => 'string' ),
					'dest'    => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => function ( array $input ): array {
				$source    = $input['source'] ?? '';
				$dest      = $input['dest'] ?? '';
				$overwrite = $input['overwrite'] ?? false;

				if ( empty( $source ) || empty( $dest ) ) {
					return array(
						'success' => false,
						'message' => 'Source and destination are required.',
					);
				}

				// Resolve paths.
				$source_path = strpos( $source, '/' ) !== 0 ? ABSPATH . $source : $source;
				$dest_path   = strpos( $dest, '/' ) !== 0 ? ABSPATH . $dest : $dest;

				$source_path = realpath( $source_path );

				if ( false === $source_path ) {
					return array(
						'success' => false,
						'message' => 'Source file not found.',
					);
				}

				// Security checks.
				$allowed_base = dirname( ABSPATH );
				if ( strpos( $source_path, $allowed_base ) !== 0 ) {
					return array(
						'success' => false,
						'message' => 'Source access denied.',
					);
				}

				$dest_dir = realpath( dirname( $dest_path ) );
				if ( false === $dest_dir || strpos( $dest_dir, $allowed_base ) !== 0 ) {
					return array(
						'success' => false,
						'message' => 'Destination access denied.',
					);
				}

				$final_dest = $dest_dir . '/' . basename( $dest_path );

				if ( file_exists( $final_dest ) && ! $overwrite ) {
					return array(
						'success' => false,
						'message' => 'Destination already exists. Use overwrite=true to replace.',
					);
				}

				if ( ! copy( $source_path, $final_dest ) ) {
					return array(
						'success' => false,
						'message' => 'Failed to copy file.',
					);
				}

				return array(
					'success' => true,
					'message' => 'File copied successfully.',
					'source'  => $source_path,
					'dest'    => $final_dest,
				);
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'manage_options' );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => false,
				),
			),
		)
	);

	// =========================================================================
	// FILESYSTEM - Move/Rename File
	// =========================================================================
	wp_register_ability(
		'filesystem/move-file',
		array(
			'label'               => 'Move/Rename File',
			'description'         => '[FILESYSTEM] Moves/renames file. DESTRUCTIVE - explain source, destination, and purpose before using.',
			'category'            => 'site',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'source'    => array(
						'type'        => 'string',
						'description' => 'Source file path.',
					),
					'dest'      => array(
						'type'        => 'string',
						'description' => 'Destination file path.',
					),
					'overwrite' => array(
						'type'        => 'boolean',
						'default'     => false,
						'description' => 'Overwrite destination if it exists.',
					),
				),
				'required'             => array( 'source', 'dest' ),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
					'message' => array( 'type' => 'string' ),
					'source'  => array( 'type' => 'string' ),
					'dest'    => array( 'type' => 'string' ),
				),
			),
			'execute_callback'    => function ( array $input ): array {
				$source    = $input['source'] ?? '';
				$dest      = $input['dest'] ?? '';
				$overwrite = $input['overwrite'] ?? false;

				if ( empty( $source ) || empty( $dest ) ) {
					return array(
						'success' => false,
						'message' => 'Source and destination are required.',
					);
				}

				// Resolve paths.
				$source_path = strpos( $source, '/' ) !== 0 ? ABSPATH . $source : $source;
				$dest_path   = strpos( $dest, '/' ) !== 0 ? ABSPATH . $dest : $dest;

				$source_path = realpath( $source_path );

				if ( false === $source_path ) {
					return array(
						'success' => false,
						'message' => 'Source file not found.',
					);
				}

				// Security checks.
				$allowed_base = dirname( ABSPATH );
				if ( strpos( $source_path, $allowed_base ) !== 0 ) {
					return array(
						'success' => false,
						'message' => 'Source access denied.',
					);
				}

				// Block moving core files.
				if ( strpos( $source_path, ABSPATH . 'wp-includes/' ) === 0 ||
					strpos( $source_path, ABSPATH . 'wp-admin/' ) === 0 ) {
					return array(
						'success' => false,
						'message' => 'Cannot move WordPress core files.',
					);
				}

				$dest_dir = realpath( dirname( $dest_path ) );
				if ( false === $dest_dir || strpos( $dest_dir, $allowed_base ) !== 0 ) {
					return array(
						'success' => false,
						'message' => 'Destination access denied.',
					);
				}

				$final_dest = $dest_dir . '/' . basename( $dest_path );

				if ( file_exists( $final_dest ) && ! $overwrite ) {
					return array(
						'success' => false,
						'message' => 'Destination already exists. Use overwrite=true to replace.',
					);
				}

				if ( ! rename( $source_path, $final_dest ) ) {
					return array(
						'success' => false,
						'message' => 'Failed to move file.',
					);
				}

				return array(
					'success' => true,
					'message' => 'File moved successfully.',
					'source'  => $source_path,
					'dest'    => $final_dest,
				);
			},
			'permission_callback' => function (): bool {
				return current_user_can( 'manage_options' );
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => false,
					'destructive' => true,
					'idempotent'  => false,
				),
			),
		)
	);
