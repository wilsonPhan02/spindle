import re

with open('welcome.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Unpack dark-universe
content = re.sub(r'<div id="dark-universe" class="relative">\s*', '', content)
content = re.sub(r'\s*</div>\s*(?=<section id="writers")', '\n\n', content)

# 2. Add fp-section class to top level tags
content = re.sub(r'(<(?:header|section|footer)[^>]*?class=")([^"]*)(")', r'\1\2 fp-section\3', content)

# 3. Combine Are you ready and Footer into one fp-section.
are_you_ready_idx = content.find('<section class="relative overflow-hidden bg-brand-50 pt-10 pb-0 fp-section">')
if are_you_ready_idx != -1:
    footer_end_idx = content.find('</footer>') + len('</footer>')
    combined_part = content[are_you_ready_idx:footer_end_idx]
    combined_part = combined_part.replace(' fp-section', '')
    new_combined = '<div class="fp-section flex flex-col">\n' + combined_part + '\n</div>'
    content = content[:are_you_ready_idx] + new_combined + content[footer_end_idx:]

# 4. Wrap everything in fp-container
start_idx = content.find('<header id="hero"')
end_idx = content.find('</footer>') + len('</footer>')
if end_idx < start_idx: 
    end_idx = content.find('</div>', content.find('<div class="fp-section flex flex-col">')) + len('</div>')

main_content = content[start_idx:end_idx]
container_html = f'''
    <style>
        .fp-section {{
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            pointer-events: none;
            transition: opacity 1.2s cubic-bezier(0.25, 1, 0.5, 1), transform 1.2s cubic-bezier(0.25, 1, 0.5, 1);
            transform: scale(0.96);
            z-index: 0;
            overflow-y: auto;
            overflow-x: hidden;
            background-color: inherit;
        }}
        .fp-section.active {{
            opacity: 1;
            pointer-events: auto;
            transform: scale(1);
            z-index: 10;
        }}
        .fp-section::-webkit-scrollbar {{ display: none; }}
        .fp-section {{ -ms-overflow-style: none; scrollbar-width: none; }}
    </style>
    <div id="fp-container" class="fixed inset-0 w-full h-full z-0 overflow-hidden bg-brand-50">
{main_content}
    </div>
'''
content = content[:start_idx] + container_html + content[end_idx:]

with open('welcome.blade.php', 'w', encoding='utf-8') as f:
    f.write(content)
