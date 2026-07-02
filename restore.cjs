const fs = require('fs');
let content = fs.readFileSync('c:/Users/forme/Documents/BCA/Cawu5/laravel-project/spindle/storage/framework/views/999EC55.tmp', 'utf8');

content = content.replace(/<\?php echo e\((.*?)\); \?>/gs, '{{ $1 }}');
content = content.replace(/<\?php echo app\('Illuminate\\\\Foundation\\\\Vite'\)\(\\\[(.*?)\]\); \?>/gs, '@vite([$1])');
content = content.replace(/<\?php echo \\$__env->make\('(.*?)', array_diff_key\(get_defined_vars\(\), \['__data' => 1, '__path' => 1\]\)\)->render\(\); \?>/gs, '@include(\'$1\')');

content = content.replace(/<\?php \\$img = fn \(\\$f\) => asset\('images\/landing\/' \. \\$f\); \?>/g, "@php $img = fn ($f) => asset('images/landing/' . $f); @endphp");
content = content.replace(/<\?php \\$books = \\[(.*?)\\]; \\?>/gs, '@php $books = [$1]; @endphp');
content = content.replace(/<\?php \\$base = asset\('images\/landing'\) \. '\/'; \?>/gs, "@php $base = asset('images/landing') . '/'; @endphp");

content = content.replace(/<\?php if\(\\Livewire.*?endif; \?>/gs, '');
content = content.replace(/<!--\\[if BLOCK\\]><!\\[endif\\]-->/gs, '');
content = content.replace(/<!--\\[if ENDBLOCK\\]><!\\[endif\\]-->/gs, '');

content = content.replace(/<\?php \\$__currentLoopData = (.*?); \\$__env->addLoop\(\\$__currentLoopData\); foreach\(\\$__currentLoopData as \\$([a-zA-Z0-9_]+) => \\$([a-zA-Z0-9_]+)\): .*? \?>/gs, '@foreach($1 as $$2 => $$3)');
content = content.replace(/<\?php \\$__currentLoopData = (.*?); \\$__env->addLoop\(\\$__currentLoopData\); foreach\(\\$__currentLoopData as \\$([a-zA-Z0-9_]+)\): .*? \?>/gs, '@foreach($1 as $$2)');
content = content.replace(/<\?php endforeach; \\$__env->popLoop\(\); \\$loop = \\$__env->getLastLoop\(\); \?>/gs, '@endforeach');

content = content.replace(/<\?php\s*$/gm, '@php');
content = content.replace(/^\s*\?>/gm, '@endphp');
content = content.replace(/<\?php \/\*\*PATH .*? \*\*\/\s*\?>/gs, '');

fs.writeFileSync('c:/Users/forme/Documents/BCA/Cawu5/laravel-project/spindle/resources/views/welcome.blade.php', content);
console.log('Restored perfectly.');
