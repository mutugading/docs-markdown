from pptx import Presentation
from pptx.util import Inches, Pt
from pptx.dml.color import RGBColor
from pptx.enum.shapes import MSO_SHAPE
from pptx.enum.text import PP_ALIGN, MSO_ANCHOR

NAVY = RGBColor(0x0F, 0x2A, 0x4A)
TEAL = RGBColor(0x0E, 0x9F, 0x9A)
AMBER = RGBColor(0xE8, 0x9B, 0x20)
RED = RGBColor(0xC8, 0x3E, 0x3E)
GREY = RGBColor(0x5B, 0x67, 0x75)
LIGHT = RGBColor(0xF1, 0xF4, 0xF8)
WHITE = RGBColor(0xFF, 0xFF, 0xFF)
DARK = RGBColor(0x1F, 0x29, 0x37)

prs = Presentation()
prs.slide_width, prs.slide_height = Inches(13.333), Inches(7.5)
BLANK = prs.slide_layouts[6]
W = prs.slide_width


def box(s, x, y, w, h, fill=None, line=None, shape=MSO_SHAPE.RECTANGLE):
    sh = s.shapes.add_shape(shape, x, y, w, h)
    if fill is None:
        sh.fill.background()
    else:
        sh.fill.solid(); sh.fill.fore_color.rgb = fill
    if line is None:
        sh.line.fill.background()
    else:
        sh.line.color.rgb = line; sh.line.width = Pt(1.25)
    sh.shadow.inherit = False
    return sh


def text(s, x, y, w, h, runs, size=16, color=DARK, bold=False, align=PP_ALIGN.LEFT,
         anchor=MSO_ANCHOR.TOP, spacing=1.1):
    """runs: str or list of paragraphs; paragraph = str or list of (text, dict) tuples."""
    tb = s.shapes.add_textbox(x, y, w, h)
    tf = tb.text_frame; tf.word_wrap = True; tf.vertical_anchor = anchor
    tf.margin_left = tf.margin_right = Inches(0.05)
    tf.margin_top = tf.margin_bottom = Inches(0.02)
    paras = runs if isinstance(runs, list) else [runs]
    for i, para in enumerate(paras):
        p = tf.paragraphs[0] if i == 0 else tf.add_paragraph()
        p.alignment = align; p.line_spacing = spacing
        parts = para if isinstance(para, list) else [(para, {})]
        for t, o in parts:
            r = p.add_run(); r.text = t
            f = r.font; f.name = 'Segoe UI'
            f.size = Pt(o.get('size', size)); f.bold = o.get('bold', bold)
            f.color.rgb = o.get('color', color)
        if isinstance(para, list) and para and para[0][1].get('space'):
            p.space_before = Pt(para[0][1]['space'])
    return tb


def header(s, title, kicker=None, n=None):
    box(s, 0, 0, W, Inches(0.12), fill=TEAL)
    if kicker:
        text(s, Inches(0.6), Inches(0.35), Inches(10), Inches(0.35), kicker.upper(), size=12, color=TEAL, bold=True)
    text(s, Inches(0.6), Inches(0.62), Inches(12), Inches(0.8), title, size=30, color=NAVY, bold=True)
    if n:
        text(s, Inches(12.2), Inches(7.0), Inches(0.8), Inches(0.3), str(n), size=11, color=GREY, align=PP_ALIGN.RIGHT)
    text(s, Inches(0.6), Inches(7.0), Inches(8), Inches(0.3), 'HRIS · Employee ID (NIK) Standardization', size=11, color=GREY)


def card(s, x, y, w, h, title, body, accent=TEAL, title_size=17, body_size=13.5):
    box(s, x, y, w, h, fill=LIGHT)
    box(s, x, y, Inches(0.08), h, fill=accent)
    text(s, x + Inches(0.25), y + Inches(0.18), w - Inches(0.4), Inches(0.5), title, size=title_size, color=NAVY, bold=True)
    text(s, x + Inches(0.25), y + Inches(0.68), w - Inches(0.4), h - Inches(0.8), body, size=body_size, color=DARK, spacing=1.15)


def bullets(items, size=14):
    return [[('•  ' + i, {'size': size})] for i in items]


n = 0
# 1 ─ Title
s = prs.slides.add_slide(BLANK); n += 1
box(s, 0, 0, W, prs.slide_height, fill=NAVY)
box(s, Inches(0.8), Inches(2.3), Inches(0.12), Inches(2.4), fill=TEAL)
text(s, Inches(1.15), Inches(2.2), Inches(11), Inches(0.5), 'HRIS TRANSFORMATION', size=16, color=TEAL, bold=True)
text(s, Inches(1.15), Inches(2.7), Inches(11.5), Inches(1.6),
     'One Employee ID, One HRIS', size=48, color=WHITE, bold=True)
text(s, Inches(1.15), Inches(3.75), Inches(11), Inches(1.0),
     'Standardizing the Employee ID (NIK) and controlling data access\nacross Jakarta, Solo, Bandung — and future partner companies',
     size=20, color=RGBColor(0xC9, 0xD6, 0xE6))
text(s, Inches(1.15), Inches(6.3), Inches(11), Inches(0.4), 'Management briefing · September 2026', size=14, color=RGBColor(0x9F, 0xB3, 0xC8))

# 2 ─ Executive summary
s = prs.slides.add_slide(BLANK); n += 1
header(s, 'Executive summary', 'In one slide', n)
text(s, Inches(0.6), Inches(1.55), Inches(12), Inches(0.9),
     'We will replace today\'s inconsistent employee numbers with one standard, system-generated ID — '
     'so Jakarta, Solo and Bandung run on a single HRIS, partner/outsourcing companies can be onboarded, '
     'and every user only sees the data they are authorized to see.', size=17, color=DARK, spacing=1.2)
cols = [
    ('What', 'New NIK format YYYYMM + 4-digit sequence, generated automatically from join date and company.', TEAL),
    ('Why', 'Integrate 3 sites in one application, prepare for multi-company / outsourcing, enforce access control.', NAVY),
    ('How', 'Phased rollout: prepare data → run old & new side by side → one cutover date → new NIK everywhere.', AMBER),
    ('Impact', 'No history lost, no fingerprint re-enrolment. Old NIK remains searchable forever.', RED),
]
cw = Inches(2.9)
for i, (t, b, c) in enumerate(cols):
    card(s, Inches(0.6) + i * (cw + Inches(0.2)), Inches(2.85), cw, Inches(3.6), t, b, accent=c, title_size=20, body_size=15)

# 3 ─ Business drivers
s = prs.slides.add_slide(BLANK); n += 1
header(s, 'Why we are changing the NIK', 'Business drivers', n)
drivers = [
    ('1', 'Integrate Jakarta, Solo & Bandung in one application',
     'Each site created employee numbers its own way. In one shared system, the same number can belong to two different people, '
     'and the same person can appear twice. One standard ID is the foundation for one HRIS.', TEAL),
    ('2', 'Ready for outsourcing & partner companies',
     'Numbering runs per company. An outsourcing vendor or new entity can be attached as its own company with its own number '
     'series — no clash with our employees, no rework of the system.', NAVY),
    ('3', 'Access control managed inside the system',
     'Who can see and approve which employees is defined by Company → Division → Department → Section and enforced centrally '
     'on every screen, report, export and approval.', AMBER),
]
for i, (num, t, b, c) in enumerate(drivers):
    y = Inches(1.6) + i * Inches(1.75)
    circ = box(s, Inches(0.6), y + Inches(0.15), Inches(0.9), Inches(0.9), fill=c, shape=MSO_SHAPE.OVAL)
    text(s, Inches(0.6), y + Inches(0.15), Inches(0.9), Inches(0.9), num, size=30, color=WHITE, bold=True,
         align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)
    text(s, Inches(1.8), y + Inches(0.05), Inches(10.9), Inches(0.5), t, size=20, color=NAVY, bold=True)
    text(s, Inches(1.8), y + Inches(0.55), Inches(10.9), Inches(1.1), b, size=15, color=DARK, spacing=1.15)

# 4 ─ Current situation
s = prs.slides.add_slide(BLANK); n += 1
header(s, 'Today: one person, many numbers', 'Current situation (development database)', n)
stats = [('6+', 'different NIK formats\n(4-digit Solo, 5–7 digit Jakarta,\n"2006-0686" payroll, EX0012, names)'),
         ('262', 'ID-card numbers that appear\non more than one employee record'),
         ('~90', 'Jakarta employees also\nregistered in Solo'),
         ('0', 'uniqueness rules on NIK today\n(duplicates already exist)')]
sw = Inches(2.9)
for i, (v, l) in enumerate(stats):
    x = Inches(0.6) + i * (sw + Inches(0.2))
    box(s, x, Inches(1.65), sw, Inches(2.2), fill=LIGHT)
    text(s, x, Inches(1.75), sw, Inches(0.9), v, size=44, color=RED if i == 3 else NAVY, bold=True, align=PP_ALIGN.CENTER)
    text(s, x + Inches(0.15), Inches(2.7), sw - Inches(0.3), Inches(1.1), l, size=13, color=GREY, align=PP_ALIGN.CENTER)
text(s, Inches(0.6), Inches(4.2), Inches(12), Inches(0.5), 'What this causes', size=18, color=NAVY, bold=True)
text(s, Inches(0.6), Inches(4.7), Inches(12), Inches(2.2), bullets([
    'Reports and headcount can double-count or mix up employees across sites.',
    'Payroll, attendance, leave and overtime rely on the NIK — a wrong match means a wrong payment or missing attendance.',
    'The NIK is also the fingerprint PIN and the login for 2,464 accounts, so fixing it ad-hoc is risky.',
    'Data access rules are applied inconsistently — some screens and exports are not restricted by site at all.',
], size=15))

# 5 ─ New format
s = prs.slides.add_slide(BLANK); n += 1
header(s, 'The new Employee ID', 'Format', n)
parts = [('2026', 'Join year', NAVY), ('10', 'Join month', TEAL), ('0004', 'Running number\nper company per month', AMBER)]
x = Inches(2.1)
for v, l, c in parts:
    w = Inches(1.2) * len(v) / 2 + Inches(1.2)
    box(s, x, Inches(1.8), w, Inches(1.4), fill=c)
    text(s, x, Inches(1.8), w, Inches(1.4), v, size=54, color=WHITE, bold=True, align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)
    text(s, x, Inches(3.3), w, Inches(0.8), l, size=14, color=GREY, align=PP_ALIGN.CENTER)
    x += w + Inches(0.15)
card(s, Inches(0.6), Inches(4.4), Inches(4.0), Inches(2.35), 'Automatic',
     'Generated by the system when HR saves a new employee. Nobody types or chooses a number.', TEAL, body_size=14)
card(s, Inches(4.75), Inches(4.4), Inches(4.0), Inches(2.35), 'Meaningful',
     'Tells you at a glance when the person joined. Same 10-digit length for every site.', NAVY, body_size=14)
card(s, Inches(8.9), Inches(4.4), Inches(3.85), Inches(2.35), 'Unique per company',
     'Each company has its own series, e.g. MGT 2026100004 and a partner company 2026100002.', AMBER, body_size=14)

# 6 ─ Lifecycle rules
s = prs.slides.add_slide(BLANK); n += 1
header(s, 'When does an employee get a new NIK?', 'Identity rules', n)
rows = [('Event', 'NIK', 'Reason'),
        ('New hire', 'New', 'Based on join date + company'),
        ('Rehire (resigned, then joins again)', 'New', 'New join date = new employment period'),
        ('Contract → Permanent', 'New', 'Permanent join date starts a new period'),
        ('Move to another company', 'New', 'Treated as a new employee of that company'),
        ('Move division / department / section (same company)', 'Same', 'Still the same employment'),
        ('Promotion, position change', 'Same', 'Still the same employment')]
tbl = s.shapes.add_table(len(rows), 3, Inches(0.6), Inches(1.6), Inches(12.1), Inches(4.2)).table
for c, wd in enumerate([Inches(6.0), Inches(1.5), Inches(4.6)]):
    tbl.columns[c].width = wd
for r, row in enumerate(rows):
    for c, v in enumerate(row):
        cell = tbl.cell(r, c); cell.text = v
        cell.fill.solid()
        cell.fill.fore_color.rgb = NAVY if r == 0 else (LIGHT if r % 2 else WHITE)
        p = cell.text_frame.paragraphs[0]; f = p.runs[0].font
        f.name = 'Segoe UI'; f.size = Pt(15); f.bold = (r == 0 or c == 1)
        f.color.rgb = WHITE if r == 0 else (TEAL if v == 'New' else (NAVY if v == 'Same' else DARK))
        if c == 1: p.alignment = PP_ALIGN.CENTER
        cell.vertical_anchor = MSO_ANCHOR.MIDDLE
text(s, Inches(0.6), Inches(6.05), Inches(12.1), Inches(0.8),
     'A "Duplicate Employee" function lets HR create the new record in one step: personal data is copied, the old record is closed, '
     'and both are linked so the full career history stays visible.', size=14, color=GREY)

# 7 ─ Multi-site & multi-company
s = prs.slides.add_slide(BLANK); n += 1
header(s, 'One platform for all sites and companies', 'Integration & outsourcing readiness', n)
# hub diagram (connectors first so the hub sits on top)
nodes = [('Solo', Inches(1.0), Inches(1.65), TEAL), ('Jakarta', Inches(5.15), Inches(1.65), TEAL), ('Bandung', Inches(9.3), Inches(1.65), TEAL),
         ('Company B', Inches(1.0), Inches(5.05), AMBER), ('Outsourcing vendor', Inches(5.15), Inches(5.05), AMBER),
         ('Future entity', Inches(9.3), Inches(5.05), AMBER)]
for name, x, y, c in nodes:
    ln = s.shapes.add_connector(1, x + Inches(1.5), y + Inches(0.4), Inches(6.65), Inches(3.75))
    ln.line.color.rgb = RGBColor(0xB8, 0xC4, 0xD2); ln.line.width = Pt(1.5)
hub = box(s, Inches(4.9), Inches(3.1), Inches(3.5), Inches(1.3), fill=NAVY, shape=MSO_SHAPE.ROUNDED_RECTANGLE)
text(s, Inches(4.9), Inches(3.1), Inches(3.5), Inches(1.3), [[('HRIS', {'size': 24, 'bold': True, 'color': WHITE})],
     [('one employee ID · one access model', {'size': 12, 'color': RGBColor(0xC9, 0xD6, 0xE6)})]],
     align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)
for name, x, y, c in nodes:
    box(s, x, y, Inches(3.0), Inches(0.8), fill=c, shape=MSO_SHAPE.ROUNDED_RECTANGLE)
    text(s, x, y, Inches(3.0), Inches(0.8), name, size=16, color=WHITE, bold=True, align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)
text(s, Inches(0.6), Inches(1.2), Inches(5), Inches(0.35), 'Our divisions (company MGT)', size=12, color=TEAL, bold=True)
text(s, Inches(0.6), Inches(6.15), Inches(12), Inches(0.35), 'Attachable companies — each with its own NIK series and its own access scope', size=12, color=AMBER, bold=True)
text(s, Inches(0.6), Inches(6.5), Inches(12.1), Inches(0.45),
     'Note: the ID and access model are ready now; bringing actual outsourcing staff data into HRIS is a separate follow-up project.',
     size=12, color=GREY)

# 8 ─ Access control
s = prs.slides.add_slide(BLANK); n += 1
header(s, 'Access control, managed in the system', 'Who can see what', n)
levels = ['Company', 'Division', 'Department', 'Section']
for i, lv in enumerate(levels):
    w = Inches(5.2) - i * Inches(0.8)
    x = Inches(0.6) + i * Inches(0.4)
    y = Inches(1.7) + i * Inches(1.05)
    box(s, x, y, w, Inches(0.85), fill=[NAVY, TEAL, AMBER, GREY][i], shape=MSO_SHAPE.ROUNDED_RECTANGLE)
    text(s, x, y, w, Inches(0.85), lv, size=18, color=WHITE, bold=True, align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)
text(s, Inches(0.6), Inches(6.0), Inches(5.2), Inches(0.8),
     'Access can be granted at any level — e.g. "all of Jakarta" or "only the Finance department in Solo".', size=13, color=GREY)
examples = [('HR Head Office', 'All companies & divisions'),
            ('HR Jakarta', 'Company MGT · Division Jakarta · all departments'),
            ('Department manager', 'Own department only — list, reports, approvals'),
            ('Outsourcing vendor admin', 'Only their own company\'s workers'),
            ('Employee (self-service)', 'Own data only')]
text(s, Inches(6.5), Inches(1.6), Inches(6.2), Inches(0.4), 'Examples', size=17, color=NAVY, bold=True)
for i, (r, a) in enumerate(examples):
    y = Inches(2.1) + i * Inches(0.72)
    box(s, Inches(6.5), y, Inches(6.2), Inches(0.62), fill=LIGHT)
    text(s, Inches(6.65), y, Inches(2.6), Inches(0.62), r, size=14, color=NAVY, bold=True, anchor=MSO_ANCHOR.MIDDLE)
    text(s, Inches(9.25), y, Inches(3.4), Inches(0.62), a, size=13, color=DARK, anchor=MSO_ANCHOR.MIDDLE)
text(s, Inches(6.5), Inches(5.8), Inches(6.2), Inches(1.0), bullets([
    'One central rule engine for screens, dashboards, exports and approvals',
    'No one can approve their own request; no access without an assigned scope'], size=13))

# 9 ─ Continuity
s = prs.slides.add_slide(BLANK); n += 1
header(s, 'No disruption to daily operations', 'Business continuity', n)
items = [('History is kept', 'Past attendance, leave, overtime and payroll stay exactly as they are. Reports that span the change date still show one employee, not two.', TEAL),
         ('Old NIK still works', 'Search, import and reports accept both old and new NIK. The old number is shown next to the new one on profiles and exports.', NAVY),
         ('No fingerprint re-enrolment', 'Existing staff keep their current PIN on the attendance machines. Only new hires get the new number as PIN.', AMBER),
         ('Logins unchanged', 'Employees keep signing in with their current user ID. Using the new NIK to log in can be enabled later.', GREY)]
for i, (t, b, c) in enumerate(items):
    x = Inches(0.6) + (i % 2) * Inches(6.15)
    y = Inches(1.65) + (i // 2) * Inches(2.6)
    card(s, x, y, Inches(5.95), Inches(2.4), t, b, c, title_size=19, body_size=15)

# 10 ─ Roadmap
s = prs.slides.add_slide(BLANK); n += 1
header(s, 'Phased rollout', 'Roadmap', n)
phases = [('0', 'Data clean-up', 'HR confirms duplicates, missing join dates & status anomalies', GREY),
          ('1', 'Side by side', 'Every employee gets a new NIK next to the old one; nothing changes for users', TEAL),
          ('2', 'New hires', 'New employees get the new NIK automatically; Duplicate Employee live; payroll tested in parallel', TEAL),
          ('3', 'Cutover date', 'New NIK becomes the ID in all modules, on the first day of a payroll period', NAVY),
          ('4', 'Access control', 'Central access rules on all screens, reports and approvals (can start alongside phase 1)', AMBER),
          ('5', 'Enhancements', 'Login with new NIK, company/division selector, retire legacy logic', GREY)]
pw = Inches(2.0)
for i, (num, t, b, c) in enumerate(phases):
    x = Inches(0.55) + i * (pw + Inches(0.05))
    box(s, x, Inches(1.8), pw, Inches(0.9), fill=c, shape=MSO_SHAPE.CHEVRON if i else MSO_SHAPE.PENTAGON)
    text(s, x + Inches(0.2), Inches(1.8), pw - Inches(0.4), Inches(0.9), 'Phase ' + num, size=16, color=WHITE, bold=True,
         align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)
    text(s, x + Inches(0.05), Inches(2.9), pw - Inches(0.1), Inches(0.5), t, size=16, color=NAVY, bold=True, align=PP_ALIGN.CENTER)
    text(s, x + Inches(0.05), Inches(3.45), pw - Inches(0.1), Inches(1.9), b, size=13, color=DARK, align=PP_ALIGN.CENTER, spacing=1.15)
box(s, Inches(0.6), Inches(5.55), Inches(12.1), Inches(1.2), fill=LIGHT)
text(s, Inches(0.85), Inches(5.65), Inches(11.7), Inches(1.0), [
    [('Cutover safeguards:  ', {'bold': True, 'color': NAVY, 'size': 14}),
     ('automated pre-checks must pass · full regression test on the development system first · backup taken automatically · '
      'rollback possible until the first payroll run · 30-day monitoring of attendance and payroll.', {'size': 14})]],
     anchor=MSO_ANCHOR.MIDDLE)

# 11 ─ Risks
s = prs.slides.add_slide(BLANK); n += 1
header(s, 'Key risks and how we manage them', 'Risks', n)
risks = [('Payroll system uses short (5-character) NIK fields', 'Widen fields with the payroll team before new NIKs reach payroll; one parallel payroll run', 'High'),
         ('Wrongly merging two different people', 'Merges only from a list approved by HR; nothing is deleted', 'Medium'),
         ('Reports split one employee into two after cutover', 'Reports link through the internal employee key, not the NIK text', 'Medium'),
         ('Incomplete master data (250 resigned without date, 196 without join date)', 'HR clean-up in phase 0; incomplete records excluded from cutover', 'Medium'),
         ('Older fingerprint machines limit PIN length', 'Existing PINs unchanged; test each machine model; shorter fallback PIN', 'Low')]
rows = [('Risk', 'Mitigation', 'Level')] + risks
tbl = s.shapes.add_table(len(rows), 3, Inches(0.6), Inches(1.6), Inches(12.1), Inches(4.9)).table
for c, wd in enumerate([Inches(5.2), Inches(5.6), Inches(1.3)]):
    tbl.columns[c].width = wd
lvl = {'High': RED, 'Medium': AMBER, 'Low': TEAL}
for r, row in enumerate(rows):
    for c, v in enumerate(row):
        cell = tbl.cell(r, c); cell.text = v; cell.fill.solid()
        cell.fill.fore_color.rgb = NAVY if r == 0 else (lvl[v] if c == 2 else (LIGHT if r % 2 else WHITE))
        p = cell.text_frame.paragraphs[0]; f = p.runs[0].font
        f.name = 'Segoe UI'; f.size = Pt(14); f.bold = (r == 0 or c == 2)
        f.color.rgb = WHITE if (r == 0 or c == 2) else DARK
        if c == 2: p.alignment = PP_ALIGN.CENTER
        cell.vertical_anchor = MSO_ANCHOR.MIDDLE

# 12 ─ Decisions
s = prs.slides.add_slide(BLANK); n += 1
header(s, 'Decisions requested from management', 'Asks', n)
asks = [('Approve the new NIK standard', 'YYYYMM + 4-digit number, unique per company, generated by the system.'),
        ('Confirm the identity rules', 'New NIK for rehire, contract → permanent and change of company; same NIK for internal moves.'),
        ('Set the cutover date', 'Recommended: first day of a payroll period, after parallel payroll testing.'),
        ('Assign data clean-up owners', 'HR per division to complete missing join/resign dates and confirm duplicates.'),
        ('Rule for past contract → permanent', '508 historical cases. Default: keep as one record; do not split history.'),
        ('Mandate central access control', 'Access defined per Company / Division / Department / Section, including partner companies.')]
for i, (t, b) in enumerate(asks):
    x = Inches(0.6) + (i % 2) * Inches(6.15)
    y = Inches(1.65) + (i // 2) * Inches(1.72)
    box(s, x, y, Inches(5.95), Inches(1.52), fill=LIGHT)
    box(s, x + Inches(0.2), y + Inches(0.36), Inches(0.8), Inches(0.8), fill=TEAL, shape=MSO_SHAPE.OVAL)
    text(s, x + Inches(0.2), y + Inches(0.36), Inches(0.8), Inches(0.8), str(i + 1), size=22, color=WHITE, bold=True,
         align=PP_ALIGN.CENTER, anchor=MSO_ANCHOR.MIDDLE)
    text(s, x + Inches(1.2), y + Inches(0.15), Inches(4.6), Inches(0.5), t, size=16, color=NAVY, bold=True)
    text(s, x + Inches(1.2), y + Inches(0.62), Inches(4.6), Inches(0.85), b, size=13, color=DARK)

# 13 ─ Close
s = prs.slides.add_slide(BLANK); n += 1
box(s, 0, 0, W, prs.slide_height, fill=NAVY)
text(s, Inches(1.0), Inches(2.3), Inches(11.3), Inches(1.0), 'One ID. One system. Controlled access.', size=40, color=WHITE, bold=True)
text(s, Inches(1.0), Inches(3.4), Inches(11.3), Inches(2.0), [
    [('Next step: ', {'bold': True, 'color': TEAL, 'size': 20}),
     ('HR data clean-up (phase 0) and technical preparation start upon approval.', {'color': WHITE, 'size': 20})],
    [('Detailed PRD, technical specification and migration plan are available for the project team.', {'color': RGBColor(0xC9, 0xD6, 0xE6), 'size': 16, 'space': 14})]])
text(s, Inches(1.0), Inches(6.3), Inches(11), Inches(0.4), 'Questions & discussion', size=18, color=TEAL, bold=True)

out = r'D:\IT Project\docs-markdown\apps-mutugading\HRIS-NIK\presentation\HRIS_NIK_Management_Briefing.pptx'
prs.save(out)
print('saved', out, n, 'slides')
