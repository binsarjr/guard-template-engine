<?php

namespace Intersec\TemplateEngine;


class Guardian {
    public $template;

   function getFile($file) {
      $this->template = file_get_contents($file);
   }

   function setup() {
      /**
       * 
       * Pembuka php
       * 
       * menggantikan:
       * @php -> <?php dan @endphp -> ?>
       * 
       * contoh: 
       * @php
       *    $name = 'Intersec';
       * @endphp
       * 
       * menjadi:
       * 
       * <?php
       *    $name = 'Intersec';
       * ?>
       */
      $this->template = preg_replace("#@php(.*?)@endphp#si", '<?php $1 ?>', $this->template);
      /**
       * 
       * php echo
       * 
       * menggantikan:
       * { @var } -> <?= @var ?>
       * contoh: 
       * 
       * { $data }
       * 
       * menjadi:
       * 
       * <?= $data ?>
       * 
       */
      $this->template = preg_replace("#{(?:(|\s)+)(.*?)(?:(|\s)+)}#sim", '<?= $2; ?>', $this->template);
      /**
       * 
       * php foreach dengan if empty
       * 
       * menggantikan:
       * @foreach()     -> <?php if( !empty(@var) ){ foreach(@var as @var){ ?>
       * @empty         -> <?php } }else{ ?>
       * @endforeach    -> <?php } ?>
       * contoh: 
       * 
       * @foreach(@var sa @var)
       *    // lakukan sesuatu
       * @empty
       *    tidak ada data yang tersedia
       * @endforeach
       * 
       * menjadi:
       * 
       * <?php
       * if( !empty(@var) ) {
       *    foreach(@var as @var) {
       *       // lakukan sesuatu
       *    }
       * } else {
       *    // lakukan sesuatu
       * }
       * ?>
       * 
       */
      $this->template = preg_replace('#@foreach\(((|[\s]+?)(.*?)(\s+)as(\s+)(.*?)(|[\s]+?)?)\)$(.*?)@empty(.*?)@endforeach#sim', '<?php if( !empty($3) ){ foreach($1){ ?>$8<?php } }else{ ?>$9<?php } ?>', $this->template);
      /**
       * 
       * php foreach biasa
       * 
       * menggantikan:
       * @foreach()     -> <?php foreach(@var as @var){ ?>
       * @endforeach    -> <?php } ?>
       * contoh: 
       * 
       * @foreach(@var sa @var)
       *    // lakukan sesuatu
       * @endforeach
       * 
       * menjadi:
       * 
       * <?php
       * foreach(@var as @var) {
       *    // lakukan sesuatu
       * }
       * ?>
       * 
       */
      $this->template = preg_replace('#@foreach\((.*?)\)(.*?)@endforeach#sim', '<?php foreach($1) { ?>$2<?php } ?>', $this->template);
      /**
       * 
       * php for loop
       * 
       * menggantikan:
       * @for()     -> <?php for(){ ?>
       * @endfor    -> <?php } ?>
       * contoh: 
       * 
       * @for()
       *    // lakukan sesuatu
       * @endfor
       * 
       * menjadi:
       * 
       * <?php
       * for() {
       *    // lakukan sesuatu
       * }
       * ?>
       * 
       */
      $this->template = preg_replace('#@for\((.*?)\)$(.*?)@endfor#sim', '<?php for($1) { ?>$2<?php } ?>', $this->template);
   }


   function output() {
      eval("?>".$this->template);
   }
}

$guard = new Guardian();
$guard->getFile('Template.guard.php');
$guard->setup();
// echo $guard->template;
$guard->output();