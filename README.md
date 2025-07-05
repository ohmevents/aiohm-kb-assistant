# AIOHM - Knowledge Base Assistant

**Version:** 1.1.1  
**Author:** AIOHM  
**Author URI:** [https://aiohm.com](https://aiohm.com)  
**WordPress Compatibility:** 5.0+  
**PHP Compatibility:** 7.4+

Transform your WordPress site into an AI-powered expert. The AIOHM Knowledge Assistant scans your website's content and embeds it into a powerful knowledge base, allowing you to provide instant, accurate answers to your visitors through an interactive chat assistant.

## How It Works

The AIOHM Knowledge Assistant makes it incredibly simple to build and deploy your own custom-trained AI:

1.  **Scan Your Content**: Automatically crawl your posts, pages, and readable media library files (`.txt`, `.csv`, `.json`).
2.  **Build the Knowledge Base**: With one click, add the scanned content to your AI's knowledge base. The plugin uses vector embeddings to capture the meaning of your content, not just keywords.
3.  **Deploy the Chat**: Add the AI chat assistant to your site using a simple shortcode or enable the floating widget to provide help everywhere.

## Core Features

  * **AI-Powered Chat Assistant**: Provide intelligent, human-like conversation powered by your website's content.
  * **Semantic Search Functionality**: Implements a powerful search engine that understands the *meaning* of a query, not just keywords.
  * **Comprehensive Content Scanner**: Finds all published posts and pages, showing you what's already in the knowledge base and what's ready to be added.
  * **Readable File Processing**: Indexes content from text-based files (`.json`, `.txt`, `.csv`) in your Media Library.
  * **Membership Content Control**: Includes built-in integration for the **ARMember Lite** plugin. Filter knowledge and tailor AI responses based on a user's membership level.
  * **Personal AI Brand Soul**: For users with an AIOHM Tribe account, a guided questionnaire helps define your brand's voice and story, creating a unique personality for your AI.
  * **Knowledge Base Management**: Easily view, manage, and delete entries from your knowledge base. You can also export your global knowledge base to a JSON file for backup.
  * **Powerful Admin Dashboard**: A central command center to manage settings, scan content, view your knowledge base, and access AIOHM membership features.
  * **Easy Deployment with Shortcodes**: Place your chat and search interfaces anywhere with simple, customizable shortcodes.

## Installation

1.  Download the plugin `.zip` file from the marketplace.
2.  In your WordPress dashboard, navigate to `Plugins` \> `Add New`.
3.  Click `Upload Plugin` and select the `.zip` file you downloaded.
4.  Click `Install Now` and then `Activate Plugin`.

## Configuration

After activation, you will see a new **AIOHM** menu in your WordPress dashboard.

1.  **Go to `AIOHM` \> `Settings`**:
      * Enter your **OpenAI API Key**. This is required for the AI to function.
      * Customize the **Custom Instructions (System Prompt)** to give your AI its unique personality and instructions.
2.  **Go to `AIOHM` \> `Scan Content`**:
      * Click `Scan Website` to find all your posts and pages.
      * Select the content you want to use and click `Add / Re-index Selected` to build your knowledge base.
3.  **Deploy the Assistant**:
      * Add the `[aiohm_chat]` or `[aiohm_search]` shortcode to any post or page.

## Shortcode Usage

### Chat Shortcode

```
[aiohm_chat title="Ask Me Anything" placeholder="Type your question..." welcome_message="Hello! How can I help you today?"]
```

  * `title`: The text displayed in the chat header.
  * `placeholder`: The placeholder text in the input field.
  * `welcome_message`: An initial message from the bot.

### Search Shortcode

```
[aiohm_search placeholder="Search the knowledge base..." max_results="10"]
```

  * `placeholder`: The placeholder text in the search bar.
  * `max_results`: The maximum number of results to display.

## Requirements

  - WordPress 5.0 or higher
  - PHP 7.4 or higher
  - An active OpenAI API Key
  - Minimum 128MB PHP memory limit

## Support

For technical support or questions, please visit our official website at [aiohm.com](https://aiohm.com).

## Changelog

**Version 1.2.0**

  - New professional README for marketplace.
  - Minor bug fixes and stability improvements.

**Version 1.0.0**

  - Initial release.

## License

This plugin is proprietary software developed by AIOHM. All rights reserved.
