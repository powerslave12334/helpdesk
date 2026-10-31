import re

path = r'C:\Users\Cristhian.Zepeda\Downloads\02. HELPDESK\HELPDESK\resources\views\tickets\index.blade.php'

with open(path, 'r', encoding='utf8') as f:
    content = f.read()

# Remover los divs data-tipo-ticket="revision_gps" que quedan
# Patrones a remover:
# 1. El select Problema GPS con data-tipo-ticket="revision_gps"
# 2. El input Especifica problema con data-tipo-ticket="revision_gps"
# 3. Actualizar los remaining data-tipo-ticket de "falla_tecnica,revision_gps" a "falla_tecnica"

# Remover el bloque completo Problema GPS + select
pattern1 = r'<div class="mb-3" data-tipo-ticket="revision_gps">\s*<label class="form-label">Problema GPS \*?</label>\s*<select[^>]*>.*?</select>\s*</div>'
content = re.sub(pattern1, '', content, flags=re.DOTALL)

# Remover el bloque Especifica problema
pattern2 = r'<div class="mb-3" data-tipo-ticket="revision_gps">\s*<label class="form-label">Especifica el problema \*?</label>\s*<input type="text"[^>]*>\s*</div>'
content = re.sub(pattern2, '', content, flags=re.DOTALL)

# Actualizar data-tipo-ticket de "falla_tecnica,revision_gps" a "falla_tecnica"
content = content.replace('data-tipo-ticket="falla_tecnica,revision_gps"', 'data-tipo-ticket="falla_tecnica"')
content = content.replace("data-tipo-ticket=\"falla_tecnica,revision_gps\"", 'data-tipo-ticket="falla_tecnica"')

with open(path, 'w', encoding='utf8') as f:
    f.write(content)

print("Done - length:", len(content))