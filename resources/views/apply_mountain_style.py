import re

with open('welcome.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

style_block = """
    <style>
        @keyframes mountainRise {
            0% { transform: translateY(150px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }
        .animate-mountain-rise-1 {
            animation: mountainRise 1.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
        }
        .animate-mountain-rise-2 {
            animation: mountainRise 1.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            animation-delay: 0.15s;
            opacity: 0;
        }
        .animate-mountain-rise-3 {
            animation: mountainRise 1.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            animation-delay: 0.3s;
            opacity: 0;
        }
    </style>
</head>
"""

content = content.replace('</head>', style_block)

with open('welcome.blade.php', 'w', encoding='utf-8') as f:
    f.write(content)
