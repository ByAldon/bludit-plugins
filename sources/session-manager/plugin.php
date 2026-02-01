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

        // Belangrijke herinnering voor de gebruiker
        $html  = '<div class="alert alert-warning">';
        $html .= '<i class="fa fa-info-circle"></i> <strong>Note:</strong> Changes will only take effect after you click <b>"Save"</b>. You may need to log out and log in again for the browser to refresh its session cookies.';
        $html .= '</div>';

        $html .= '<div class="mb-3">';
        $html .= '<label class="form-label font-weight-bold">Select Session Mode</label>';
        $html .= '<select id="jsmode" name="mode" class="form-control">';
        $html .= '<option value="default" '.($currentMode === 'default' ? 'selected' : '').'>Bludit Default</option>';
        $html .= '<option value="browser_based" '.($currentMode === 'browser_based' ? 'selected' : '').'>Browser Based</option>';
        $html .= '<option value="manual" '.($currentMode === 'manual' ? 'selected' : '').'>Manual Duration</option>';
        $html .= '</select>';
        $html .= '</div>';

        // Beschrijvingen (worden getoond via JS)
        $html .= '<div id="desc-default" class="alert alert-secondary session-desc" style="display:none;">';
        $html .= 'Uses Bludit\'s standard session handling. The system decides when you need to log in again based on core settings.';
        $html .= '</div>';

        $html .= '<div id="desc-browser" class="alert alert-secondary session-desc" style="display:none;">';
        $html .= 'Keep your session active as long as the browser remains open. You will be logged out immediately when you close all browser windows.';
        $html .= '</div>';

        $html .= '<div id="desc-manual" class="alert alert-secondary session-desc" style="display:none;">';
        $html .= 'Set a strictly defined session length in seconds. This persists even if you close the browser, until the time runs out.';
        $html .= '</div>';

        // Handmatig invoerveld
        $html .= '<div id="manual-input-container" class="mb-3 mt-3" style="display:none;">';
        $html .= '<label class="form-label font-weight-bold">Manual Session Timeout (seconds)</label>';
        $html .= '<input name="customTimeout" type="number" value="'.$currentTimeout.'" class="form-control">';
        $html .= '<span class="tip">Example: 3600 = 1 hour. See <a href="https://www.php.net/manual/en/session.configuration.php#ini.session.gc-maxlifetime" target="_blank">PHP Docs</a>.</span>';
        $html .= '</div>';

        $html .= '<script>
            function updateSessionUI() {
                var mode = document.getElementById("jsmode").value;
                document.querySelectorAll(".session-desc").forEach(el => el.style.display = "none");
                document.getElementById("manual-input-container").style.display = "none";

                if (mode === "default") {
                    document.getElementById("desc-default").style.display = "block";
                } else if (mode === "browser_based") {
                    document.getElementById("desc-browser").style.display = "block";
                } else if (mode === "manual") {
                    document.getElementById("desc-manual").style.display = "block";
                    document.getElementById("manual-input-container").style.display = "block";
                }
            }
            updateSessionUI();
            document.getElementById("jsmode").addEventListener("change", updateSessionUI);
        </script>';

        return $html;
    }

    public function beforeAdminLoad() {
        $mode = $this->getValue('mode');

        if ($mode === 'browser_based') {
            ini_set('session.cookie_lifetime', 0);
            ini_set('session.gc_maxlifetime', 86400); 
            session_set_cookie_params(0);
        } 
        elseif ($mode === 'manual') {
            $timeout = (int)$this->getValue('customTimeout');
            if ($timeout > 0) {
                ini_set('session.gc_maxlifetime', $timeout);
                session_set_cookie_params($timeout);
            }
        }
    }
}