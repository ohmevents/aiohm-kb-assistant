# AIOHM Knowledge Assistant WordPress Plugin

**Version:** 1.0.0  
**Author:** AIOHM Development Team  
**WordPress Compatibility:** 5.0+  
**PHP Compatibility:** 7.4+

## Description

The AIOHM Knowledge Assistant is a powerful WordPress plugin that transforms your website into an intelligent knowledge base. It scans your content, creates vector embeddings, and provides AI-powered chat and search functionality to help visitors find information quickly and accurately.

## Features

- **Intelligent Content Scanning**: Automatically crawls posts, pages, and navigation menus
- **File Processing**: Supports PDF and image upload with OCR text extraction
- **AI Integration**: Compatible with OpenAI GPT and Anthropic Claude models
- **Vector Search**: Advanced semantic search using embeddings
- **Chat Interface**: Interactive AI chat widget with shortcode support
- **Search Functionality**: Instant search with content filtering
- **Admin Dashboard**: Complete management interface for settings and knowledge base

## Installation

### Method 1: WordPress Admin (Recommended)
1. Download the plugin zip file
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Choose the downloaded zip file and click "Install Now"
4. Activate the plugin

### Method 2: Manual Installation
1. Extract the plugin files
2. Upload the `aiohm-kb-assistant` folder to `/wp-content/plugins/`
3. Go to WordPress Admin → Plugins
4. Find "AIOHM Knowledge Assistant" and click "Activate"

## Configuration

1. **Access Settings**: Go to WordPress Admin → AIOHM Settings
2. **Add API Key**: Enter your OpenAI or Claude API key
3. **Configure Models**: Select your preferred AI model and settings
4. **Enable Features**: Turn on chat and search functionality
5. **Scan Content**: Run initial content scan to build knowledge base

## Usage

### Chat Widget
Add the chat interface anywhere using shortcode:
```
[aiohm_chat title="Ask me anything" height="400"]
```

### Search Functionality
Add search functionality using shortcode:
```
[aiohm_search placeholder="Search knowledge base..." max_results="10"]
```

### Floating Chat
The plugin can automatically add a floating chat widget to your site. Enable this in the plugin settings.

## Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **API Key**: OpenAI API key or Anthropic Claude API key
- **Memory**: Minimum 128MB PHP memory limit
- **Storage**: Adequate space for vector embeddings storage

## API Keys Setup

### OpenAI API Key
1. Visit [OpenAI Platform](https://platform.openai.com/)
2. Create an account or sign in
3. Go to API Keys section
4. Generate a new secret key
5. Add the key to plugin settings

### Anthropic Claude API Key
1. Visit [Anthropic Console](https://console.anthropic.com/)
2. Create an account or sign in
3. Go to API Keys section
4. Generate a new API key
5. Add the key to plugin settings

## File Structure

```
aiohm-kb-assistant/
├── aiohm-kb-assistant.php          # Main plugin file
├── includes/
│   ├── core-init.php               # Core initialization
│   ├── crawler-site.php            # Website content crawler
│   ├── crawler-uploads.php         # File upload crawler
│   ├── rag-engine.php              # Vector embeddings engine
│   ├── ai-gpt-client.php           # AI API integration
│   ├── settings-page.php           # Admin settings page
│   ├── aiohm-kb-manager.php        # Knowledge base manager
│   ├── shortcode-chat.php          # Chat shortcode handler
│   ├── shortcode-search.php        # Search shortcode handler
│   ├── chat-box.php                # Chat UI components
│   └── frontend-widget.php         # Frontend assets
└── assets/
    ├── js/aiohm-chat.js            # Chat JavaScript
    └── css/aiohm-chat.css          # Plugin styling
```

## Troubleshooting

### Common Issues

**Plugin doesn't activate**
- Check PHP version (7.4+ required)
- Verify WordPress version (5.0+ required)
- Check for plugin conflicts

**Chat not working**
- Verify API key is correctly entered
- Check API key permissions
- Ensure sufficient API credits

**Search returns no results**
- Run content scan from plugin settings
- Check if content indexing completed
- Verify database permissions

### Support

For technical support and documentation:
- Check plugin settings page for status indicators
- Review WordPress error logs for detailed error messages
- Ensure API keys have proper permissions and credits

## Changelog

### Version 1.0.0
- Initial release
- Core chat and search functionality
- OpenAI and Claude API integration
- Content scanning and vector embeddings
- Admin dashboard and settings
- Shortcode support

## License

This plugin is proprietary software developed by AIOHM. All rights reserved.

## Credits

Developed with modern WordPress standards and best practices for optimal performance and security.