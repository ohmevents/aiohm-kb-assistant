# AIOHM Knowledge Assistant

**Contributors:** AIOHM  
**Tags:** ai, chatbot, knowledge-base, artificial-intelligence, chat, assistant, customer-support, faq, search, automation  
**Requires at least:** 5.0  
**Tested up to:** 6.4  
**Requires PHP:** 7.4  
**Stable tag:** 1.2.0  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  

Transform your WordPress site into an intelligent knowledge hub with AI-powered chat assistance, content scanning, and brand-aligned responses.

## Description

The AIOHM Knowledge Assistant brings advanced AI capabilities to your WordPress site, creating an intelligent chat experience that understands your content and speaks in your brand voice. Perfect for businesses, educators, and content creators who want to provide instant, accurate information to their visitors.

### 🌟 Key Features

**🔍 Smart Content Scanner**
- Automatically scans WordPress posts, pages, and media files
- Supports text-based formats (`.txt`, `.json`, `.csv`)
- Intelligent content chunking and vectorization
- Scheduled scanning for automatic updates

**💬 AI Chat Assistant**
- Multiple AI provider support (OpenAI, Gemini, Claude, Ollama)
- Customizable chat interface with brand colors and styling
- Real-time knowledge base search and retrieval
- Responsive design that works on all devices

**📚 Knowledge Base Management**
- Add, edit, and delete knowledge entries
- Export/import knowledge base as JSON
- Content categorization and tagging
- Bulk operations for efficient management

**🎨 Brand Personalization**
- Mirror Mode: Q&A assistant for customer support
- Muse Mode: Brand-aligned content generation
- Custom brand archetypes (Creator, Sage, Hero, etc.)
- Personalized voice and tone settings

**🔐 Membership Integration**
- Support for membership plugins (PMP, ARMember)
- Role-based access control
- Private content handling
- Tiered feature access

**⚙️ Advanced Settings**
- Multiple AI provider integration
- Custom temperature and model settings
- Private LLM server support (Ollama)
- Comprehensive usage analytics

### 🚀 Perfect For

- **Customer Support**: Instant answers to common questions
- **Education**: Interactive learning experiences
- **E-commerce**: Product information and guidance
- **Content Sites**: Enhanced content discovery
- **Business**: Brand-consistent communication

### 🛠️ AI Provider Support

- **OpenAI**: GPT-3.5, GPT-4
- **Google Gemini**: Gemini Pro, Gemini 1.5
- **Anthropic Claude**: Claude 3 Sonnet, Haiku, Opus
- **Ollama**: Self-hosted private LLM servers

## Installation

### Automatic Installation

1. Log into your WordPress admin dashboard
2. Navigate to `Plugins > Add New`
3. Search for "AIOHM Knowledge Assistant"
4. Click "Install Now" and then "Activate"

### Manual Installation

1. Download the plugin zip file
2. Go to `Plugins > Add New > Upload Plugin`
3. Select the downloaded zip file and click "Install Now"
4. Activate the plugin

### After Installation

1. Go to `AIOHM > Settings`
2. Configure your AI provider API keys
3. Set up your assistant's voice and personality
4. Run your first content scan
5. Deploy the chat assistant using shortcodes

## Configuration

### Basic Setup

1. **API Configuration**
   - Navigate to `AIOHM > Settings`
   - Enter your preferred AI provider API key
   - Test the connection to ensure it's working

2. **Content Scanning**
   - Go to `AIOHM > Scan Content`
   - Select content types to scan
   - Run the scan and add content to knowledge base

3. **Assistant Customization**
   - Use Mirror Mode for Q&A functionality
   - Use Muse Mode for content generation
   - Customize colors, greetings, and behavior

### Advanced Features

**Private LLM Integration**
- Configure Ollama servers for private AI processing
- Available for premium members
- Complete data privacy and control

**Brand Soul Setup**
- Define your brand's core values and voice
- Create custom response templates
- Align AI responses with your brand personality

## Shortcodes

### Chat Assistant
```
[aiohm_chat title="Ask Me Anything" placeholder="Type your question..." welcome_message="Hello! How can I help you today?"]
```

**Parameters:**
- `title`: Chat window title
- `placeholder`: Input field placeholder text
- `welcome_message`: Initial greeting message

### Knowledge Search
```
[aiohm_search placeholder="Search our knowledge base..." max_results="10"]
```

**Parameters:**
- `placeholder`: Search input placeholder
- `max_results`: Maximum number of results to display

### Private Assistant (Premium)
```
[aiohm_private_assistant]
```

## Frequently Asked Questions

### Do I need an AI API key?

Yes, you need an API key from at least one supported AI provider (OpenAI, Gemini, Claude, or Ollama). We recommend starting with OpenAI or Gemini for the best experience.

### How does the knowledge base work?

The plugin scans your WordPress content and creates a searchable knowledge base using AI embeddings. When visitors ask questions, the AI searches relevant content and provides contextual answers.

### Can I use my own AI models?

Yes! Premium members can connect private Ollama servers for complete data privacy and control over AI processing.

### Is the plugin mobile-friendly?

Absolutely! The chat interface is fully responsive and works seamlessly on all devices.

### How do I customize the assistant's personality?

Use the Mirror Mode and Muse Mode settings to define your assistant's voice, choose brand archetypes, and create custom response templates.

### Can I export my knowledge base?

Yes, you can export your entire knowledge base as JSON for backup or migration purposes.

## Screenshots

1. **Dashboard Overview** - Main dashboard with usage statistics and quick actions
2. **Content Scanning** - Intelligent content scanning interface
3. **Chat Interface** - Responsive chat assistant in action
4. **Knowledge Base Management** - Easy content management tools
5. **Brand Customization** - Mirror Mode and Muse Mode settings
6. **AI Provider Settings** - Multiple AI provider configuration

## Changelog

### 1.2.0
* Added Ollama private server support
* Implemented comprehensive multi-AI provider system
* Enhanced Mirror Mode and Muse Mode functionality
* Added brand archetype templates
* Improved WordPress coding standards compliance
* Fixed security escaping issues
* Added custom OHM logo menu integration
* Enhanced membership integration system

### 1.1.11
* Improved content scanning accuracy
* Enhanced chat interface responsiveness
* Added usage analytics dashboard
* Fixed various bugs and performance issues

### 1.1.1
* Enhanced shortcode support
* Added onboarding flow for voice setup
* Improved knowledge base search
* Better error handling and user feedback

### 1.0.0
* Initial plugin release
* Basic AI chat functionality
* Content scanning capabilities
* WordPress integration

## Upgrade Notice

### 1.2.0
This major update adds multiple AI provider support, private LLM integration, and enhanced brand customization features. Please backup your site before upgrading.

## Support

**Documentation**: [https://aiohm.app/docs](https://aiohm.app/docs)  
**Support Forum**: [https://aiohm.app/support](https://aiohm.app/support)  
**GitHub Issues**: [https://github.com/aiohm/kb-assistant/issues](https://github.com/aiohm/kb-assistant/issues)  

For premium support and advanced features, visit [AIOHM.app](https://aiohm.app).

## Privacy Policy

This plugin connects to third-party AI services (OpenAI, Google, Anthropic) to process content and generate responses. Please review the privacy policies of your chosen AI provider. Private LLM options are available for users requiring complete data privacy.

## License

This plugin is licensed under the GPLv2 or later. Some premium features may require additional licensing.