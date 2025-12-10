# MCP Expose Abilities

A WordPress plugin that exposes WordPress functionality as MCP (Model Context Protocol) abilities, enabling AI assistants to interact with your WordPress site.

## Requirements

- WordPress 6.9+
- PHP 8.0+
- [Abilities API](https://wordpress.org/plugins/abilities-api/) plugin
- [MCP Adapter](https://wordpress.org/plugins/mcp-adapter/) plugin

## Installation

1. Install and activate the required plugins (Abilities API and MCP Adapter)
2. Download the latest release zip from [Releases](https://github.com/bjornfix/mcp-expose-abilities/releases)
3. Upload via WordPress Admin → Plugins → Add New → Upload Plugin
4. Activate the plugin

## Available Abilities

### Content Management

| Ability | Description |
|---------|-------------|
| `content/list-posts` | List posts with filtering by status, category, author, search |
| `content/get-post` | Get single post by ID or slug (includes content, categories, tags, meta) |
| `content/create-post` | Create new post |
| `content/update-post` | Update existing post (only provided fields updated) |
| `content/delete-post` | Delete post (trash or permanent) |
| `content/patch-post` | Surgical find/replace in post content (plain text or regex) |
| `content/list-pages` | List pages with filtering |
| `content/list-categories` | List all post categories |
| `content/list-tags` | List all post tags |
| `content/list-media` | List media library items |
| `content/list-users` | List site users with roles |
| `content/search` | Search across posts, pages, media |

### Plugins

| Ability | Description |
|---------|-------------|
| `plugins/upload` | Upload and install plugin from URL (auto-activate, overwrite support) |
| `plugins/list` | List installed plugins with status/version |

### Elementor

| Ability | Description |
|---------|-------------|
| `elementor/get-data` | Get Elementor JSON data for a page/post |
| `elementor/update-data` | Replace entire Elementor JSON (clears CSS cache) |
| `elementor/patch-data` | Find/replace in Elementor JSON with validation |
| `elementor/list-templates` | List saved Elementor templates |
| `elementor/clear-cache` | Clear Elementor CSS cache (single post or site-wide) |

### GeneratePress

| Ability | Description |
|---------|-------------|
| `generatepress/get-settings` | Get theme settings (colors, typography, layout) |
| `generatepress/update-settings` | Update settings (merges with existing) |

### GenerateBlocks

| Ability | Description |
|---------|-------------|
| `generateblocks/get-global-styles` | Get global styles and defaults |
| `generateblocks/update-global-styles` | Update global styles |
| `generateblocks/clear-cache` | Clear CSS cache |

### Core

| Ability | Description |
|---------|-------------|
| `core/get-site-info` | Site information |
| `core/get-user-info` | Current user profile |
| `core/get-environment-info` | Runtime/PHP/DB info |

## Usage with Claude Code

Add the MCP server to Claude Code:

```bash
claude mcp add wordpress-mysite "https://yoursite.com/wp-json/mcp/mcp-adapter-default-server" \
  -t http \
  -H "Authorization: Basic YOUR_BASE64_CREDENTIALS"
```

Generate credentials:
1. Go to WordPress Admin → Users → Your Profile
2. Scroll to "Application Passwords"
3. Create a new application password
4. Base64 encode: `echo -n "username:application-password" | base64`

## Examples

### Patch post content (surgical edit)

```json
{
  "ability_name": "content/patch-post",
  "parameters": {
    "id": 123,
    "find": "old text",
    "replace": "new text"
  }
}
```

### Get Elementor page data

```json
{
  "ability_name": "elementor/get-data",
  "parameters": {
    "id": 456
  }
}
```

### Upload and activate a plugin

```json
{
  "ability_name": "plugins/upload",
  "parameters": {
    "url": "https://example.com/plugin.zip",
    "activate": true,
    "overwrite": true
  }
}
```

## Architecture

This plugin is the "abilities layer" in a three-plugin architecture:

1. **[Abilities API](https://wordpress.org/plugins/abilities-api/)** - WordPress.org official framework for registering abilities
2. **[MCP Adapter](https://wordpress.org/plugins/mcp-adapter/)** - WordPress.org official MCP protocol layer
3. **MCP Expose Abilities** (this plugin) - Custom abilities for your site

The first two are maintained by WordPress.org. This plugin simply registers the abilities you want exposed.

## License

GPL-2.0+

## Author

[Devenia](https://devenia.com)
