# Flyer bauen

Wie aus der SVG-Vorlage das PDF, die Vorschaugrafik und das Teilen-Bild entstehen.

## Woher die Vorlage kommt

Die bearbeitbare Quelle ist `website-grafiken/schulinventar-flyer.svg` — eine A4-Seite,
`viewBox="0 0 595 842"`, Texte und Farben direkt im SVG als Code.

> **Achtung:** `website-grafiken/` steht in der `.gitignore`. Im Repo liegen nur die
> fertigen Dateien, nicht die Vorlage. Wer den Flyer ändern will, braucht diesen Ordner
> vom Arbeitsrechner — es gibt keine Kopie im Repo. Wenn das lästig wird: den Eintrag aus
> der `.gitignore` nehmen und den Ordner mitversionieren.

## Ergebnisdateien

| Datei | Größe | Wo sie auftaucht |
|---|---|---|
| `schulinventar-flyer.pdf` | A4, 1 Seite | Knopf „PDF öffnen“ in `flyer.html`, „Flyer ansehen“ in `index.html` |
| `img/flyer-vorschau.png` | 900 × 1274 | Blatt-Vorschau in `flyer.html` (Breite und Höhe stehen im `<img>`-Tag) |
| `og-flyer.png` | 1200 × 630 | `og:image` und `twitter:image` in `flyer.html` — die Messenger-Vorschau |

Ändern sich die Maße, müssen `width`/`height` im `<img>`-Tag von `flyer.html` mitgezogen
werden, sonst springt das Layout beim Laden.

## Schritt 1 — `a4.html` aus dem SVG erzeugen

Chromium druckt nicht das SVG, sondern eine HTML-Seite, die es inline enthält. Der
`@import` der Google-Fonts muss dabei aus dem SVG heraus in ein `<link rel="stylesheet">`
im Head wandern: als `@import` innerhalb eines gedruckten SVG lädt Chromium die Schriften
nicht, und der Flyer kommt in Arial statt in Archivo, Public Sans und Space Mono heraus.

Aus dem Projektordner:

```bash
cd website-grafiken
python3 - <<'EOF'
import io, re
svg = io.open('schulinventar-flyer.svg', encoding='utf-8').read()
svg = re.sub(r'^<\?xml[^>]*\?>\s*', '', svg)
imp = re.search(r"@import url\('([^']+)'\);", svg)
href = imp.group(1).replace('&amp;', '&')
svg = svg.replace(imp.group(0), '')
io.open('a4.html', 'w', encoding='utf-8').write("""<!doctype html>
<meta charset="utf-8">
<title>a4.html</title>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="%s">
<style>
  @page { size: A4; margin: 0; }
  html, body { margin: 0; padding: 0; }
  svg { display: block; width: 210mm; height: 297mm; }
</style>
%s
""" % (href, svg))
EOF
```

## Schritt 2 — PDF drucken

```bash
chromium --headless=new --disable-gpu --no-sandbox --disable-extensions \
  --disable-features=DnsOverHttps,AsyncDns --force-prefers-reduced-motion \
  --virtual-time-budget=15000 --no-pdf-header-footer \
  --print-to-pdf=../schulinventar-flyer.pdf a4.html
```

`--disable-features=DnsOverHttps,AsyncDns` ist auf dem Raspberry Pi nötig, sonst hängt
Chromium beim Laden der Google-Fonts bis zum Timeout, obwohl `curl` dieselbe Adresse in
Millisekunden holt. `--virtual-time-budget` gibt den Schriften Zeit zum Ankommen.

Prüfen: `pdfinfo ../schulinventar-flyer.pdf` muss `Page size: 594.96 x 841.92 pts (A4)`
und `Pages: 1` melden.

## Schritt 3 — Vorschaubilder rastern

```bash
cd ..
pdftoppm -png -scale-to-x 900  -scale-to-y 1274 -f 1 -l 1 schulinventar-flyer.pdf img/flyer-vorschau
mv -f img/flyer-vorschau-1.png img/flyer-vorschau.png

pdftoppm -png -scale-to-x 1400 -scale-to-y 1980 -f 1 -l 1 schulinventar-flyer.pdf website-grafiken/sheet
```

Das zweite Bild (`website-grafiken/sheet-1.png`) ist nur Zwischenprodukt für Schritt 4 und
kann danach weg.

## Schritt 4 — Teilen-Bild bauen

`website-grafiken/og-card.html` ist die 1200 × 630 große Karte: links Text in den lokalen
DM-Schriften aus `fonts/`, rechts das leicht gedrehte Flyer-Blatt aus Schritt 3.

```bash
cd website-grafiken
chromium --headless=new --disable-gpu --no-sandbox --disable-extensions \
  --force-prefers-reduced-motion --hide-scrollbars \
  --window-size=1200,630 --virtual-time-budget=8000 \
  --screenshot=../og-flyer.png og-card.html
rm -f sheet-1.png
```

Hier braucht es keine DNS-Schalter — die Schriften kommen aus `../fonts/`.

## Fallstrick: das `@` in Space Mono

Das `@` von Space Mono ist breiter gezeichnet als sein Vorschub und läuft in den nächsten
Buchstaben hinein. Im Fuß las sich die Adresse dadurch als „infoawebnetzone.de“. Weder
kleinere Schrift noch `letter-spacing` lösen das sauber; nötig ist Abstand auf beiden
Seiten des Zeichens:

```xml
<text …>info<tspan dx="2">@</tspan><tspan dx="2">webnetzone.de</tspan></text>
```

Wer den Fuß umbaut, sollte das Ergebnis in echter Größe ansehen — bei 300 dpi
hochgezoomt wirkt jede Berührung dramatischer, als sie gedruckt ist, und in der
900-px-Vorschau erkennt man umgekehrt zu wenig. Ein Ausschnitt aus
`img/flyer-vorschau.png` bei doppelter Skalierung trifft es gut.

## Nach dem Bauen

`schulinventar-flyer.pdf`, `img/flyer-vorschau.png` und `og-flyer.png` gehören ins Repo
und gehen mit dem nächsten Push live. Messenger halten die alte Vorschau danach noch
Stunden bis Tage im Cache; zum Testen ein `?v=2` an den Link hängen.
