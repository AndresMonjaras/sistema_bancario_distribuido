from pathlib import Path
from docx import Document
from docx.shared import Inches,Pt,RGBColor
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
import html,re
root=Path(__file__).resolve().parent.parent/'entrega_examen';text=(root/'README.md').read_text();doc=Document();sec=doc.sections[0];sec.top_margin=Inches(.65);sec.bottom_margin=Inches(.65);sec.left_margin=Inches(.7);sec.right_margin=Inches(.7)
style=doc.styles['Normal'];style.font.name='Calibri';style.font.size=Pt(11)
footer=sec.footer.paragraphs[0];footer.text='Sistema Bancario Distribuido · Examen práctico · ';field=OxmlElement('w:fldSimple');field.set(qn('w:instr'),'PAGE');footer._p.append(field)
htmlparts=['<!doctype html><html lang="es"><meta charset="utf-8"><title>Sistema Bancario Distribuido</title><style>body{font:15px/1.6 Arial,sans-serif;max-width:1000px;margin:32px auto;color:#16324b}h1,h2{color:#102a43}figure{margin:20px 0;page-break-inside:avoid}img{width:100%;border:1px solid #dce5ec}figcaption{font-size:12px;text-align:center;color:#536b7b;margin-top:8px}@media print{body{margin:0}h2{break-before:page}a{color:inherit}}</style><body>']
for line in text.splitlines():
 if not line.strip():continue
 if line.startswith('# '):doc.add_heading(line[2:],0);htmlparts.append('<h1>'+html.escape(line[2:])+'</h1>')
 elif line.startswith('## '):doc.add_heading(line[3:],1);htmlparts.append('<h2>'+html.escape(line[3:])+'</h2>')
 elif line.startswith('!['):
  m=re.match(r'!\[(.*?)\]\((.*?)\)',line);p=root/m.group(2)
  if p.exists():doc.add_picture(str(p),width=Inches(6.8));htmlparts.append('<figure><img src="'+html.escape(m.group(2))+'" alt="'+html.escape(m.group(1))+'">')
 elif line.startswith('*Figura '):
  caption=line.strip('*');p=doc.add_paragraph(caption);p.alignment=1;p.runs[0].italic=True;p.runs[0].font.size=Pt(9);htmlparts.append('<figcaption>'+html.escape(caption)+'</figcaption></figure>')
 else:clean=line.replace('**','');doc.add_paragraph(clean);htmlparts.append('<p>'+html.escape(clean)+'</p>')
doc.save(root/'Documentacion_funcional.docx');htmlparts.append('</body></html>');(root/'Documentacion_funcional.html').write_text('\n'.join(htmlparts));print('Documentación funcional creada: README, DOCX y HTML.')
