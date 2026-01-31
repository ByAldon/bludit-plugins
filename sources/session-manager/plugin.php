<?php

class pluginSession_manager extends Plugin {

    public function name() {
        return "Session Manager";
    }

    public function description() {
        return "Manage login sessions with dynamic options and manual overrides.";
    }

    public function init() {
        $this->dbFields = array(
            'mode' => 'default',
            'customTimeout' => 3600
        );
    }

    public function form() {
        $currentMode = $this->getValue('mode');
        $currentTimeout = $this->getValue('customTimeout');

        $html  = '<div class="mb-3">';
        $html .= '<label class="form-label">Session Mode</label>';
        $html .= '<select id="jsmode" name="mode" class="form-control">';
        $html .= '<option value="default" '.($currentMode === 'default' ? 'selected' : '').'>Bludit Default</option>';
        $html .= '<option value="browser_based" '.($currentMode === 'browser_based' ? 'selected' : '').'>Browser Based (Stay logged in while open)</option>';
        $html .= '<option value="manual" '.($currentMode === 'manual' ? 'selected' : '').'>Manual Duration (Set specific time)</option>';
        $html .= '</select>';
        $html .= '</div>';

        // Dit veld is verborgen tenzij 'manual' is geselecteerd
        $html .= '<div id="manual-input-container" class="mb-3" style="display:none;">';
        $html .= '<label class="form-label">Manual Session Timeout (seconds)</label>';
        $html .= '<input name="customTimeout" type="number" value="'.$currentTimeout.'" class="form-control">';
        $html .= '<span class="tip">Enter duration in seconds. Example: 3600 = 1 hour. See <a href="https://www.php.net/manual/en/session.configuration.php#ini.session.gc-maxlifetime" target="_blank">PHP Docs</a>.</span>';
        $html .= '</div>';

        // JavaScript om de zichtbaarheid te regelen
        $html .= '<script>
            function toggleManualInput() {
                var mode = document.getElementById("jsmode").value;
                var container = document.getElementById("manual-input-container");
                if (mode === "manual") {
                    container.style.display = "block";
                } else {
                    container.style.display = "none";
                }
            }
            // Uitvoeren bij laden pagina
            toggleManualInput();
            // Uitvoeren bij wijziging dropdown
            document.getElementById("jsmode").addEventListener("change", toggleManualInput);
        </script>';

        return $html;
    }

    public function beforeAdminLoad() {
        $mode = $this->getValue('mode');

        if ($mode === 'browser_based') {
            // Logica voor: ingelogd blijven tot browser sluit
            ini_set('session.cookie_lifetime', 0);
            ini_set('session.gc_maxlifetime', 86400); // 24 uur veiligheid
            session_set_cookie_params(0);
        } 
        elseif ($mode === 'manual') {
            // Logica voor: specifieke handmatige tijd
            $timeout = (int)$this->getValue('customTimeout');
            ini_set('session.gc_maxlifetime', $timeout);
            session_set_cookie_params($timeout);
        }
        // Bij 'default' doen we niets, Bludit regelt het zelf.
    }
}