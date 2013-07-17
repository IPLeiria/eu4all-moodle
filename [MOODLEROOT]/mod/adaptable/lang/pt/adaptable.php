<?php

/**
 * Strings for component 'adaptable', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package     mod-adaptable
 * @version		2010.0
 * @copyright 	Learning Distance Unit {@link http://ued.ipleiria.pt}, Polytechnic Institute of Leiria
 * @author 		Catarina Maximiano <catarina.maximiano@ipleiria.pt>, Cláudio Esperança <claudio.esperanca@ipleiria.pt>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['modulename'] = 'Adaptable';
$string['name'] = 'Adaptable';
$string['modulenameplural'] = 'Adaptables';
$string['clicktoopen'] = 'Clique na hiperligação {$a} para abrir o recurso.';
$string['configdisplayoptions'] = 'Selecione todas as opções que devem estar disponíveis. Definições existentes não são modificadas. Pressione a tecla CTRL para seleccionar múltiplos campos.';
$string['configframesize'] = 'Quando uma página web ou um ficheiro são apresentados numa moldura, este valor corresponde à altura (em pixeis) da moldura de topo (que contém a navegação).';
$string['configrolesinparams'] = 'Active se pretende incluir nomes de papéis traduzidos na lista de parâmetros disponíveis.';
$string['configsecretphrase'] = 'Esta frase secreta é utilizada para produzir código cifrado que pode ser enviado para alguns servidores como um parâmtero. O código cifrado é produzido utilizando um valor md5 do endereço IP do utilizador actual concatenado com a sua frase secreta. código exemplo = md5(IP.frasesecreta. Note que isto não é seguro pois o endereço IP pode sofrer alterações e muitas vezes partilhado por diferentes computadores.';
$string['displayoptions'] = 'Opções de apresentação disponíveis';
$string['displayselect'] = 'Visualização';
$string['displayselectexplain'] = 'Escolha o tipo de visualização. Infelizmente nem todos os tipos de visualização são adequados a todos os recursos.';
$string['framesize'] = 'Altura da moldura';
$string['chooseavariable'] = 'Especifique uma variável...';
$string['neverseen'] = 'Nunca visto';
$string['optionsheader'] = 'Opções';
$string['parameterinfo'] = 'parâmetro=variável';
$string['parametersheader'] = 'Parâmetros';
$string['pluginname'] = 'Adaptable';
$string['popupheight'] = 'Altura do popup (em pixeis)';
$string['popupheightexplain'] = 'Define a altura por omissão das janelas de popup.';
$string['popupwidth'] = 'Largura do popup (em pixeis)';
$string['popupwidthexplain'] = 'Especifica a largura por omissão das janelas de popup.';
$string['printheading'] = 'Nome do recurso a apresentar';
$string['printheadingexplain'] = 'Mostra o nome do recurso sobre o conteúdo? Alguns tipos de visualização podem não mostrar o nome do recurso mesmo se esta opção estiver activa.';
$string['printintro'] = 'Mostrar a descrição do recurso';
$string['printintroexplain'] = 'Mostrar a descrição do recurso a seguir ao conteúdo? Alguns tipos de visualização podem não mostrar a descrição do recurso mesmo se esta opção estiver activa.';
$string['rolesinparams'] = 'Incluir o nome dos papéis nos parâmetros';
$string['serverurl'] = 'Endereço do servidor';
$string['urladministration'] = 'Endereço da administração';

$string['defaultresourceheader'] = 'Recurso por omissão';
$string['intro'] = 'Introdução';
$string['defaultResource'] = 'Recurso';
$string['defaultResource_help'] = 'O recurso a apresentar por omissão';
$string['defaultResourceOriginalMode'] = 'Modo original';
$string['defaultResourceOriginalMode_help'] = 'O modo original para o recurso por omissão';
$string['defaultResourceOriginalContentType'] = 'Tipo de conteúdo original';
$string['defaultResourceOriginalContentType_help'] = 'O tipo de conteúdo original para o recurso por omissão';
$string['err_usedResourceAsDefaultResource'] = 'O mesmo recurso não pode ser utilizado como o recurso por omissão e como um recurso alternativo. Por favor, seleccione outro recurso diferente.';
$string['err_usedResourceInAnotherAlternativeResource'] = 'O mesmo recurso já está a ser utilizado como alternativa {$a}. Por favor, seleccione outro recurso diferente.';
$string['err_atLeastAnAssociatedResourceMustExist'] = 'Deve existir pelo menos um recurso disponível para ser associado como recurso por omissão.';
$string['err_youCannotAddThisResource'] = 'Não pode adicionar este recurso.';

$string['addResourceAlternativeHeader'] = 'Adicionar um recurso alternativo';
$string['resourceAlternativeAdd'] = 'Adicionar um novo recurso alternativo';
$string['resourceAlternativeRemove'] = 'Remover o recurso alternativo {$a}';
$string['resourceAlternative'] = 'Recurso alternativo {$a}:';
$string['resourceAlternativeResource'] = 'Recurso alternativo';
$string['resourceAlternativeResource_help'] = 'O recurso alternativo para o recurso por omissão';
$string['resourceAlternativeOriginalMode'] = 'Modo original';
$string['resourceAlternativeOriginalMode_help'] = 'Modo original para a alternativa';
$string['resourceAlternativeAdaptationType'] = 'Tipo de adaptação';
$string['resourceAlternativeAdaptationType_help'] = 'Tipo de adaptação para a alternativa';
$string['resourceAlternativeRepresentationForm'] = 'Forma de representação';
$string['resourceAlternativeRepresentationForm_help'] = 'Forma de representação para a alternativa';

// adaptation type
$string['adaptationTypeAU'] = 'representação audio (AU)';
$string['adaptationTypeVI'] = 'representação visual (VI)';
$string['adaptationTypeTE'] = 'representação textual (TE)';
$string['adaptationTypeTA'] = 'representação táctil (TA)';
$string['adaptationTypeCA'] = 'legendagem (CA)';
$string['adaptationTypeAD'] = 'audiodescrição (AD)';
$string['adaptationTypeBR'] = 'braille (BR)';
$string['adaptationTypeDI'] = 'livro digital falado (DI)';
$string['adaptationTypeEL'] = 'livro electrónico (EL)';

// access mode
$string['accessModeV'] = 'visual (V)';
$string['accessModeX'] = 'textual (X)';
$string['accessModeA'] = 'audível (A)';
$string['accessModeT'] = 'tátil (T)';
$string['accessModeO'] = 'olfativo (O)';

// representation form
$string['representationFormVoid'] = 'não definida (void)';
$string['representationFormEN'] = 'melhorada (EN)';
$string['representationFormVE'] = 'textual (VE)';
$string['representationFormRD'] = 'reduzida (RD)';
$string['representationFormRT'] = 'em tempo real (RT)';
$string['representationFormTR'] = 'transcrita (TR)';
$string['representationFormAL'] = 'texto alternativo (AL)';
$string['representationFormLO'] = 'descrição longa (LO)';
$string['representationFormSI'] = 'linguagem gestual (SI)';
$string['representationFormIM'] = 'baseada em imagens (IM)';
$string['representationFormSY'] = 'simbólica (SY)';
$string['representationFormRE'] = 'gravada (RE)';
$string['representationFormSZ'] = 'sintetizada (SZ)';
$string['representationFormHA'] = 'tátil (HA)';
