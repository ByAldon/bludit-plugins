<?php

class pluginBackToTop extends Plugin {

    public function init()
    {
        $this->dbFields = array(
            'backgroundColor' => '#007bff',
            'arrowColor' => '#ffffff',
            'position' => 'right',
            'margin' => '20',
            'enableFade' => true
        );
    }

    // Handmatige fallback voor de naam
    public function name()
    {
        global $L;
        $name = $L->get('plugin-data.name');
        return ($name !== 'plugin-data.name') ? $name : 'Back To Top';
    }

    // Handmatige fallback voor de beschrijving
    public function description()
    {
        global $L;
        $desc = $L->get('plugin-data.description');
        return ($desc !== 'plugin-data.description') ? $desc : 'A customizable button to smoothly scroll back to the top of the page.';
    }

    public function form()
    {
        global $L;

        $html  = '<div class="alert alert-primary" role="alert">'.$this->description().'</div>';

        $html .= '<div>';
        $html .= '<label>'.$L->get('background-color').'</label>';
        $html .= '<input name="backgroundColor" type="color" class="form-control form-control-color" value="'.$this->getValue('backgroundColor').'">';
        $html .= '</div>';

        $html .= '<div class="mt-3">';
        $html .= '<label>'.$L->get('arrow-color').'</label>';
        $html .= '<input name="arrowColor" type="color" class="form-control form-control-color" value="'.$this->getValue('arrowColor').'">';
        $html .= '</div>';

        $html .= '<div class="mt-3">';
        $html .= '<label>'.$L->get('position').'</label>';
        $html .= '<select name="position" class="form-control">';
        $html .= '<option value="right" '.($this->getValue('position')==='right'?'selected':'').'>'.$L->get('right').'</option>';
        $html .= '<option value="left" '.($this->getValue('position')==='left'?'selected':'').'>'.$L->get('left').'</option>';
        $html .= '</select>';
		$html .= '</div>';

        $html .= '<div class="mt-3">';
        $html .= '<label>'.$L->get('margin').'</label>';
        $html .= '<input name="margin" type="number" class="form-control" value="'.$this->getValue('margin').'">';
        $html .= '</div>';

        $html .= '<div class="mt-3 form-check">';
        $html .= '<input name="enableFade" id="enableFade" type="checkbox" class="form-check-input" '.($this->getValue('enableFade')?'checked':'').'>';
        $html .= '<label class="form-check-label" for="enableFade">'.$L->get('enable-fade').'</label>';
        $html .= '</div>';

        return $html;
    }

    public function siteHead()
    {
        $bgColor = $this->getValue('backgroundColor');
        $arrowColor = $this->getValue('arrowColor');
        $pos = $this->getValue('position');
        $margin = $this->getValue('margin') . 'px';
        $transition = $this->getValue('enableFade') ? 'transition: opacity 0.4s, visibility 0.4s, transform 0.3s;' : 'transition: transform 0.3s;';

        return "
        <style>
            #back-to-top {
                position: fixed !important; bottom: $margin !important; $pos: $margin !important;
                visibility: hidden; opacity: 0; background-color: $bgColor !important;
                width: 50px; height: 50px; cursor: pointer; z-index: 99999; border-radius: 50%;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2); display: flex !important;
                align-items: center; justify-content: center; $transition
            }
            #back-to-top.show { visibility: visible; opacity: 1; }
            #back-to-top:hover { transform: translateY(-5px); opacity: 0.9; }
            #back-to-top svg { width: 24px; height: 24px; fill: none; stroke: $arrowColor !important; stroke-width: 3; stroke-linecap: round; stroke-linejoin: round; }
        </style>
        ";
    }

    public function siteBodyEnd()
    {
        return '<div id="back-to-top" onclick="window.scrollTo({top: 0, behavior: \'smooth\'});"><svg viewBox="0 0 24 24"><path d="M18 15l-6-6-6 6"></path></svg></div>
        <script>
            window.addEventListener("scroll", function() {
                var btn = document.getElementById("back-to-top");
                if (window.pageYOffset > 300) btn.classList.add("show");
                else btn.classList.remove("show");
            });
        </script>';
    }
}