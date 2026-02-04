# Flag-Filtering Implementation

## Übersicht

Diese Implementierung erweitert das bestehende Flag-System um eine dynamische Filter-UI, die es Benutzern ermöglicht, nach verschiedenen Flags zu filtern.

## Implementierte Features

### 1. Backend-Erweiterungen

#### Controller (IndexController.php)
- **Erweiterte `result` Action**: Unterstützt jetzt `flags[]` URL-Parameter
- **Rückwärtskompatibilität**: Bestehende `?sustainable=true` URLs funktionieren weiterhin
- **Flag-Parameter-Verarbeitung**: Konvertiert `sustainable` automatisch zu `flags[sustainable]=true`

#### QualityCheckService
- **Bereits implementiert**: `getResultStats()` und `getResultCount()` unterstützen `$flags` Parameter
- **Flag-Definitionen**: Zentrale Verwaltung über `FLAG_DEFINITIONS` und state-spezifische Klassen

#### Result Entity
- **Bereits implementiert**: `getCountAnswered()`, `statsByCategory()` und `shouldIncludeAnswer()` unterstützen Flag-Filtering

### 2. Frontend-Erweiterungen

#### Template (result.html.twig)
- **Dynamische Filter-UI**: Checkboxen für alle verfügbaren Flags
- **Responsive Design**: Bootstrap-Grid für verschiedene Bildschirmgrößen
- **Visuelle Indikatoren**: Icons und Farben aus Flag-Definitionen
- **Data-Attribute**: Flag-Daten werden als `data-flags` an Fragen-Elementen gespeichert

#### JavaScript-Funktionalität
- **`applyFilters()`**: Wendet ausgewählte Filter an und lädt Seite neu
- **`clearAllFilters()`**: Entfernt alle Filter und lädt Basis-URL
- **`applyFlagFilters()`**: Client-seitige Filterung für bessere UX
- **Event-Listener**: Automatische Reaktion auf Checkbox-Änderungen

#### CSS-Styling
- **Benutzerfreundliche UI**: Hover-Effekte, klare Struktur
- **Responsive Design**: Anpassung an verschiedene Bildschirmgrößen
- **Konsistente Farben**: Verwendung der Flag-Definition-Farben

## URL-Struktur

### Neue Flag-Parameter
```
/quality_check/result/123?flags[sustainable]=true&flags[miniCheck]=true
```

### Rückwärtskompatibilität
```
/quality_check/result/123?sustainable=true
```

### Kombination möglich
```
/quality_check/result/123?sustainable=true&flags[miniCheck]=true
```

## Flag-Definitionen

### Zentrale Definitionen (QualityCheckService)
```php
public const FLAG_DEFINITIONS = [
    'sustainable' => [
        'icon' => 'fas fa-leaf',
        'description' => 'Nachhaltigkeitskriterium',
        'color' => '#006600'
    ],
    'miniCheck' => [
        'icon' => 'fas fa-clipboard-check',
        'description' => 'Mini-Check',
        'color' => '#000099'
    ],
];
```

### State-spezifische Erweiterungen
```php
// src/Service/FlagDefinitions/ByFlags.php
class ByFlags
{
    public static function getFlagDefinitions(): array
    {
        return [
            'guidelineCheck' => [
                'description' => 'Leitlinien Check',
                'icon' => 'fas fa-thumbs-up',
                'color' => '#0079ac'
            ],
        ];
    }
}
```

## Verwendung

### 1. Filter auswählen
- Benutzer können beliebige Kombinationen von Flags auswählen
- Checkboxen zeigen Icons und Beschreibungen aus Flag-Definitionen
- "Filter anwenden" Button lädt Seite mit neuen Parametern

### 2. Filter löschen
- "Alle Filter löschen" Button entfernt alle Filter
- Zurück zur Basis-URL ohne Parameter

### 3. Statistik-Updates
- Statistiken werden automatisch basierend auf aktiven Filtern berechnet
- Gauge-Chart zeigt gefilterte Ergebnisse
- Kategorie-Statistiken werden entsprechend angepasst

## Technische Details

### Datenfluss
1. **URL-Parameter** → Controller verarbeitet `flags[]` Parameter
2. **Service-Layer** → `getResultStats()` und `getResultCount()` mit Flag-Filtering
3. **Entity-Layer** → `shouldIncludeAnswer()` prüft Flag-Bedingungen
4. **Template** → Flag-Daten werden als `data-flags` Attribute gespeichert
5. **JavaScript** → Client-seitige Filterung für bessere UX

### Performance
- **Server-seitige Filterung**: Hauptlogik läuft im Backend
- **Client-seitige Ergänzung**: JavaScript für sofortige visuelle Updates
- **Caching**: Symfony-Cache für Flag-Definitionen

### Erweiterbarkeit
- **Neue Flags**: Einfach in `FLAG_DEFINITIONS` oder state-spezifischen Klassen hinzufügen
- **State-spezifisch**: Jeder Bundesstaat kann eigene Flags definieren
- **Template-Integration**: Automatische Anzeige neuer Flags in der UI

## Testing

### Manuelle Tests
1. **Basis-Funktionalität**: `/quality_check/result/123`
2. **Sustainable-Filter**: `/quality_check/result/123?sustainable=true`
3. **Flag-Filter**: `/quality_check/result/123?flags[sustainable]=true`
4. **Kombination**: `/quality_check/result/123?flags[sustainable]=true&flags[miniCheck]=true`

### Automatisierte Tests
- Controller-Tests für URL-Parameter-Verarbeitung
- Service-Tests für Flag-Filtering-Logik
- Entity-Tests für `shouldIncludeAnswer()` Methoden

## Nächste Schritte

### Mögliche Erweiterungen
1. **AJAX-Filtering**: Sofortige Updates ohne Seitenneuladen
2. **Filter-Presets**: Vordefinierte Filter-Kombinationen
3. **Export mit Filtern**: PDF-Export mit aktiven Filtern
4. **Filter-Historie**: Speichern häufig verwendeter Filter-Kombinationen

### Optimierungen
1. **Performance**: Caching von gefilterten Ergebnissen
2. **UX**: Loading-Indikatoren während Filter-Anwendung
3. **Accessibility**: ARIA-Labels und Keyboard-Navigation
4. **Mobile**: Touch-optimierte Filter-UI

## Fazit

Die Implementierung bietet eine flexible und erweiterbare Lösung für Flag-basiertes Filtering, die sowohl die bestehende Funktionalität erhält als auch neue Möglichkeiten eröffnet. Die Rückwärtskompatibilität gewährleistet, dass bestehende URLs weiterhin funktionieren. 