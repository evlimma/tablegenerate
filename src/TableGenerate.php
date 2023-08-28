<?php

namespace EvLimma\TableGenerate;

class TableGenerate
{
    private $find;
    private $primary;
    private $registryNo;
    private $hrefRow;
    private $sorter;
    private $activeThead;
    private $justLine;
    private $activeBtAdd;
    private $title = null;
    private $width = null;
    private $field = null;
    private $content = null;

    /**
     * 
     * @param array|null $find
     * @param string $primary
     * @param string $registryNo
     * @param string $hrefRow
     * @param bool $sorter
     * @param bool $activeThead
     * @param bool $justLine
     * @param array $activeBtAdd            $activeBtAdd = ['action' => 'URL', <br>
     *                                                      'locationButton' => 'bottom ou top', <br>
     *                                                      'fieldWhite' => false ou true, <br>
     *                                                      'notDelFirstline' => false ou true, <br>
     *                                                      'validateHead' => false ou true]
     * @param bool $disabledMinWidth
     * @param array $lineTotal              [int (posição que vai aparecer o valor) => string ("sum", "sumNum", "media" ou "count" ou "valor livre, exemplo: 'Valor total'")]
     */
    public function __construct(
            ?array $find, 
            string $primary = null, 
            string $registryNo = 'Não há registros no momento', 
            mixed $hrefRow = null, 
            bool $sorter = false,
            bool $activeThead = true,
            bool $justLine = false,
            array $activeBtAdd = null,
            bool $disabledMinWidth = false,
            ?array $lineTotal = null)
    {
        $this->find = $find;
        $this->primary = $primary;
        $this->registryNo = $registryNo;
        $this->hrefRow = $hrefRow;
        $this->sorter = ($this->find ? $sorter : false);
        $this->activeThead = $activeThead;
        $this->justLine = $justLine;
        
        if ($activeBtAdd) {
            $activeBtAdd['locationButton'] = !empty($activeBtAdd['locationButton']) ? $activeBtAdd['locationButton'] : 'bottom';
            $activeBtAdd['fieldWhite'] = !empty($activeBtAdd['fieldWhite']) ? $activeBtAdd['fieldWhite'] : false;
            $activeBtAdd['notDelFirstline'] = !empty($activeBtAdd['notDelFirstline']) ? $activeBtAdd['notDelFirstline'] : false;
            $activeBtAdd['validateHead'] = !empty($activeBtAdd['validateHead']) ? $activeBtAdd['validateHead'] : false;
        }
        
        $this->activeBtAdd = $activeBtAdd;
        $this->disabledMinWidth = $disabledMinWidth;
        $this->lineTotal = $lineTotal;
    }

    public function colsSend(string $width, array $content, ?array $field = null, ?string $title = null): void
    {
        $typeWidth = right($width, 1) === "%" ? "" : "style='width: {$width};'";
        
        $this->title .= "      <th {$typeWidth}>{$title}" . ($this->sorter ? "<span class='ordenarClassificar'></span>" : "") . "</th>\n";
        
        $this->width[] = $width;
        $this->field[] = $field;
        $this->content[] = $content;
    }

    public function render(): string
    {
        $btAddline = null;
        $lineExtra = null;
        if (!empty($this->activeBtAdd)) {
            $btAddline = "<a href='{$this->activeBtAdd['action']}' title='Adicionar' class='btAddline' validateHead='{$this->activeBtAdd['validateHead']}'></a>";
        }
        
        if ((!empty($this->activeBtAdd) && $this->activeBtAdd['fieldWhite']) || $this->justLine) {
            $lineExtra .= "   <tr>\n";
            
            $o = 0;
            //Coluna
            foreach ($this->field as $col) { 
                $str = implode('', $this->content[$o]);
                
                $u = 0;
                if ($col) {
                    foreach ($col as $val) {
                        $u++;

                        $str = str_replace('§' . $u . '§', $this->find[0]->{$val} ?? '', $str);
                    }
                }

                $lineExtra .= "      <td><span style='min-width: " . $this->width[$o] . ";'>";
                $lineExtra .= "          {$str}";
                $lineExtra .= "      </span></td>\n";

                $o++;
            }
            $lineExtra .= "   </tr>\n";

            $lineExtra = str_replace("btIco nuvem", "btIco upload", $lineExtra);
            
            if ($this->justLine) {
                return $lineExtra;
            }
        }
        
        $html = "<div class='containerTblPadrao'>";
        $html .= !empty($this->activeBtAdd) && $this->activeBtAdd['locationButton'] === "top" ? $btAddline : "";
        $html .= "<table class='tblPadrao caixa " . ($this->sorter ? "tablesorter" : "") . ($this->disabledMinWidth ? " disabledMinWidth" : "") . "'>\n";

        if (!$this->find && !$this->activeBtAdd) {
            $html .= "<thead><tr><th><span>{$this->registryNo}</span></th></tr></thead>";
        } else {
            if ($this->activeThead) {
                $hideThead = (!$this->find && !$this->activeBtAdd['fieldWhite']) ? 'clean' : null;

                $html .= "  <thead class='{$hideThead}'>\n";
                $html .= "    <tr>\n";

                $html .= $this->title;

                $html .= "     </tr>\n";
                $html .= "  </thead>\n";
            }
            
            $html .= "  <tbody>\n";
            
            $html .= !empty($this->activeBtAdd) && $this->activeBtAdd['locationButton'] === "top" ? $lineExtra : "";
            
            //Linha
            $valorCalc = [];
            if ($this->find) {
                //Faz esse loop apenas pra declarar a variável
                $o = 0;
                foreach ($this->field as $col) {            
                    $valorCalc[$o] = 0;
                    $o++;
                }
                
                //linha
                foreach ($this->find as $list) {
                    $html .= "   <tr class='" . ($this->hrefRow ? "linkActive" : "") . "' idLinha='" . ($this->primary ? $list->{$this->primary} : '') . "'>\n";

                    $o = 0;
                    //Coluna
                    foreach ($this->field as $col) {
                        $str = implode('', $this->content[$o]);
                        
                        if ($col) {
                            $u = 0;
                            foreach ($col as $val) {
                                $u++;

                                $str = str_replace('§' . $u . '§', $list->{$val} ?? '', $str);
                            }
                        }

                        $html .= "      <td><span style='min-width: " . $this->width[$o] . ";'>";
                        
                        if ($this->lineTotal && array_key_exists($o, $this->lineTotal)) {
                            if (funEncontraPalavra("sum", $this->lineTotal[$o]) or $this->lineTotal[$o] === "media") {
                                $valorCalc[$o] += convertValorPtIn($list->{$val});
                            } elseif ($this->lineTotal[$o] === "count") {
                                ++$valorCalc[$o];
                            }
                        }
                        
                        if ($this->hrefRow) {
                            $html .= "          <a href='{$this->hrefRow}/" . ($this->primary ? $list->{$this->primary} : '') . "'>{$str}</a>";
                        } else {
                            $html .= "          {$str}";
                        }

                        $html .= "      </span></td>\n";

                        $o++;
                    }
                    $html .= "   </tr>\n";
                }
            }
            
            $html .= !empty($this->activeBtAdd) && $this->activeBtAdd['locationButton'] === "bottom" ? $lineExtra : "";
            
            $html .= "  </tbody>\n";
        }
        
        // Resumo total
        if ($this->find && $this->lineTotal) {
            $html .= "  <tfoot>\n";
            $html .= "    <tr>\n";

            $o = 0;
            foreach ($this->field as $col) {
                if ($this->lineTotal && array_key_exists($o, $this->lineTotal)) {
                    if (funEncontraPalavra("sum", $this->lineTotal[$o])) {
                        $html .= "      <td><span class='valorTotalNum' style='min-width: " . $this->width[$o - 1] . ";'>" . convertValorInPt($valorCalc[$o], 2, !funEncontraPalavra("Num", $this->lineTotal[$o])) . "</span></td>\n";
                    } elseif ($this->lineTotal[$o] === "count") {
                        $html .= "      <td><span class='valorTotalNum' style='min-width: " . $this->width[$o - 1] . ";'>{$valorCalc[$o]}</span></td>\n";
                    } elseif ($this->lineTotal[$o] === "media") {
                        $media = convertValorInPt($valorCalc[$o] / count($this->find));
                        $html .= "      <td><span class='valorTotalNum' style='min-width: " . $this->width[$o - 1] . ";'>R$ {$media}</span></td>\n";
                    } elseif ($this->lineTotal[$o] !== "") {
                        $html .= "      <td><span class='valorTotal' style='min-width: " . $this->width[$o] . ";'>{$this->lineTotal[$o]}</span></td>\n";
                    }
                } else {
                    $html .= "      <td><span style='min-width: " . $this->width[$o] . ";'></span></td>\n";
                }
                
                $o++;
            }

            $html .= "     </tr>\n";
            $html .= "  </tfoot>\n";
        }
        
        $html .= "  </table>";
        $html .=    !empty($this->activeBtAdd) && $this->activeBtAdd['locationButton'] === "bottom" ? $btAddline : "";
        $html .= "</div>";
        
        if (!empty($this->activeBtAdd) && $this->activeBtAdd['notDelFirstline']) {
            return preg_replace("/btIco delete/", "btIco delete clean", $html, 1);
        } else {
            return $html;
        }
    }

}
