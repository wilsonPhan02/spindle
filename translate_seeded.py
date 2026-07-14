import json
import re
from deep_translator import GoogleTranslator

# Terms to protect from translation
TERMS = [
    "Three-Act Structure",
    "The Setup",
    "The Confrontation",
    "The Resolution",
    "Dan Harmon's Story Circle",
    "Plot Embryo",
    "Hero's Journey",
    "Freytag's Pyramid",
    "The Five Commandments",
    "Story Grid",
    "Save the Cat!",
    "Save The Cat!",
    "Seven Point Story",
    "7-Point Story Structure",
    "27 Chapter Method",
    "Act I",
    "Act 1",
    "Act II",
    "Act 2",
    "Act III",
    "Act 3",
    "Zone of Comfort",
    "Need or Desire",
    "Crossing a Threshold",
    "Road of Trials",
    "Get What They Wanted",
    "Pay a Heavy Price",
    "Return to Comfort",
    "Having Changed",
    "Exposition",
    "Rising Action",
    "Climax",
    "Falling Action",
    "Catastrophe",
    "Inciting Incident",
    "Turning Point Complication",
    "Crisis",
    "Resolution",
    "Opening Image",
    "Theme Stated",
    "Set-up",
    "Catalyst",
    "Debate",
    "Break into Two",
    "B Story",
    "Fun and Games",
    "Fun & Games",
    "Midpoint",
    "Bad Guys Close In",
    "All is Lost",
    "Dark Night of the Soul",
    "Break into Three",
    "Finale",
    "Final Image",
    "Hook",
    "Plot Point 1",
    "Pinch Point 1",
    "Pinch Point 2",
    "Plot Turn 2",
    "Intro",
    "Fallout",
    "Reaction / Rebellion",
    "Action",
    "Consequence",
    "Pressure",
    "Pinch",
    "Push",
    "New World",
    "Juxtaposition",
    "Buildup",
    "Reversal",
    "Trials",
    "Dedication",
    "Calm Before the Storm",
    "Plot Twist",
    "Darkest Point",
    "Power Within",
    "Action & Games",
    "Convergence",
    "The Final Battle",
    "The Status Quo",
    "Ordinary Life",
    "The Catalyst",
    "Tests & Obstacles",
    "The Resolution/Denouement",
    "New Status Quo",
    "Ordinary World",
    "Tragic Flaw",
    "Aha!",
    "Causal event",
    "Coincidental event",
    "Turning Point",
    "Irreconcilable Goods",
    "Best Bad Choice",
    "Promise of the Premise",
    "False Victory",
    "False Defeat",
    "Whiff of Death",
    "Call to Adventure"
]

# Sort terms by length descending to replace longer phrases first
TERMS.sort(key=len, reverse=True)

def protect_text(text):
    protected = text
    replacements = {}
    idx = 0
    for term in TERMS:
        # Use regex with word boundaries where possible, but for terms with punctuation, be careful.
        # Simple string replacement for exact matches:
        pattern = re.escape(term)
        # Find all occurrences
        matches = list(re.finditer(r'(?i)\b' + pattern + r'\b', protected))
        if not matches:
             # Try without word boundaries for things like "Save the Cat!"
             matches = list(re.finditer(pattern, protected, re.IGNORECASE))
             
        # Actually, let's just do a case-insensitive string replace to be safe.
        # We need to maintain the exact casing of the original term when restoring.
        for match in set(re.findall(pattern, protected, re.IGNORECASE)):
            placeholder = f"____{idx}____"
            replacements[placeholder] = match
            protected = protected.replace(match, placeholder)
            idx += 1

    return protected, replacements

def restore_text(text, replacements):
    restored = text
    for placeholder, original_term in replacements.items():
        restored = restored.replace(placeholder, original_term)
    return restored

with open('seeded_strings.json', 'r', encoding='utf-8') as f:
    strings = json.load(f)

langs = {
    'id': 'id',
    'ja': 'ja',
    'ko': 'ko',
    'zh': 'zh-CN'
}

for lang_file, lang_code in langs.items():
    print(f"Translating for {lang_file}.json...")
    with open(f'lang/{lang_file}.json', 'r', encoding='utf-8') as f:
        data = json.load(f)
    
    translator = GoogleTranslator(source='en', target=lang_code)
    
    for string in strings:
        if string not in data or data[string] == string:
            # Not translated yet, or identical
            protected, replacements = protect_text(string)
            try:
                # Splitting by newline to avoid losing formatting
                parts = protected.split('\n')
                translated_parts = []
                for p in parts:
                    if p.strip() == '':
                        translated_parts.append('')
                    else:
                        translated_parts.append(translator.translate(p))
                
                translated_protected = '\n'.join(translated_parts)
                final_string = restore_text(translated_protected, replacements)
                data[string] = final_string
            except Exception as e:
                print(f"Error translating: {e}")
                data[string] = string

    with open(f'lang/{lang_file}.json', 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=4)
        
    print(f"Saved {lang_file}.json")
