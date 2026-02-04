Hilfsfunktion für POST Values
-----------------------------
```
function printCode($array, $path = false, $top = true)
{
    $data = "";
    $delimiter = "~~|~~";
    $p = null;
    if (is_array($array)) {
        foreach ($array as $key => $a) {
            if (! is_array($a) || empty($a)) {
                if (is_array($a)) {
                    $data .= $path . "['{$key}'] = [];" . $delimiter;
                } else {
                    $data .= $path . "['{$key}'] = \"" . htmlentities(addslashes($a)) . "\";" . $delimiter;
                }
            } else {
                $data .= printCode($a, $path . "['{$key}']", false);
            }
        }
    }

    if ($top) {
        $return = "";
        foreach (explode($delimiter, $data) as $value) {
            if (! empty($value)) {
                $return .= '$postData' . $value . "<br>";
            }
        };
        return $return;
    }

    return $data;
}

if ($request->isMethod('POST')) {
    echo (printCode($_POST));exit;
}
```
