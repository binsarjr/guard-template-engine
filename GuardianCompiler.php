<?php


namespace Intersec\View;

use Exception;

class View {
   /**
    * Lokasi file
    */
   protected string $file;
   /**
    * kumpulan variables
    */
   protected array $variables = array();
   /**
    * string berupa html untuk dijadikan template
    */
   public string $view;
   /**
    * lokasi view untuk keperluan extends, includes, push, yield dan lain lain
    */
   protected string $view_path = '';
   /**
    * ekstensi yang digunakan pada template engine
    */
   protected string $ext = '.guard.php';
   /**
    * diperlukan untuk pengecekan apakah user men-include
    * file itu sendiri
    */
   protected string $include = '';
   /**
    * diperlukan untuk pengecekan apakah user men-extends
    * file itu sendiri
    */
   protected string $extends = '';

   /**
    * Mempersiapkan semuanya terlebih
    * dahulu sebelum proses dijalankan
    * 
    * @param String $file
    * @param String $view_path
    *
    */
   public function __construct(String $file, String $view_path = '') {
      $this->view_path = $view_path;
      $this->view = $this->get_file($file);
      $this->file = $file;
   }
   /**
    * Mengambil path asli dari view
    * @param String $file
    *
    */
   public function get_realPath(String $file) {
      $this->view_path = $this->view_path != '' ? $this->view_path . '/' : $this->view_path;
      $realpath = realpathi($this->view_path . implode('/', explode('.', $file)) . $this->ext);
      if(!$realpath) {
         throw new Exception("View: [$file] does not exists!");
      }
      return $realpath;
   }
   /** 
    * Menyiapkan file
   */
   public function get_file(String $file) {
      return file_get_contents($this->get_realPath($file));
   }
   /**
    * Mendaftarkan variabel
    * @param string $key
    * @param any $values
    * 
    * @return void
    */
   public function setParams($key, $values): void {
      $this->variables[$key] = $values;
   }

   /**
    * Mendaftarkan variabel
    * 
    * @return void
    */
   protected function registerVariable(): void {
      $view = "\n<?php\n";
      foreach($this->variables as $key => $value) {
         $value = json_encode($value);
         $value = preg_replace('/^"(.*)"$/','$1',$value);
         $value =  preg_replace("/'/", "\'", $value);
         if(json_decode($value) == ''){
            $view = $view . "$" . $key . ' = json_decode(json_encode(\''. $value .'\'));'."\n";
         } else {
            $view = $view . "$" . $key . ' = json_decode(\''. $value .'\');'."\n";
         }
      }
      $this->view = $view."\n?>\n".$this->view;
   }

   /**
    * 
    * Pembuka php
    * 
    * menggantikan:
    * @php -> <?php
    * @endphp -> ?>
    * 
    * 
    * @return void
    */
   protected function _compilerPhp(): void {
      $pattern = "#@php(.*?)@endphp#sim";
      $replacement = "<?php";
      $replacement .= "$1";
      $replacement .= "?>";
      $this->view = preg_replace($pattern, $replacement, $this->view);
   }

   /**
    * 
    * php echo
    * 
    * menggantikan:
    * { @var } -> <?= @var ?>
    *
    * @return void
    */
   protected function _compilerEchoPhp(): void {
      // echo raw tags
      $pattern = "#" . "{!" . "(?:(|\s)+)(.*?)(?:(|\s)+)" . "!}" . "#sim";
      $replacement = '<?= $2; ?>';
      $this->view = preg_replace($pattern, $replacement, $this->view);

      // echo escape
      $pattern = "#" . "{{{" . "(?:(|\s)+)(.*?)(?:(|\s)+)" . "}}}" . "#sim";
      $replacement = '<?= addslashes($2); ?>';
      $this->view = preg_replace($pattern, $replacement, $this->view);

      // echo biasa
      $pattern = "#" . "{{" . "(?:(|\s)+)(.*?)(?:(|\s)+)" . "}}" . "#sim";
      $replacement = '<?= htmlentities(htmlspecialchars($2)); ?>'; 
      $this->view = preg_replace($pattern, $replacement, $this->view);

   }

   /**
    * compiler untuk perulangan
    * @return void
    */
   protected function _compilerLoops(): void {
      /**
       * 
       * php foreach biasa
       * 
       * menggantikan:
       * @foreach()     -> <?php foreach(@var as @var): ?>
       * @endforeach    -> <?php endforeach; ?>
       * 
       */
      $pattern = '#@foreach\s*\((.*?)\).(((?!@empty).)*?)@endforeach#sim';
      $replacement  = "<?php foreach($1): ?>$2\n<?php endforeach; ?>";
      $this->view = preg_replace($pattern, $replacement, $this->view);
      /**
       * 
       * php foreach dengan if empty
       * 
       * menggantikan:
       * @foreach()     -> <?php if( !empty(@var) ):?>
       *                      <?php foreach():?>
       *                         // content
       *                      <?php endforeach;?>
       * @empty         -> <?php else: ?>
       *                      // content
       * @endforeach    -> <?php endif; ?>
       * 
       */
      $pattern = '#@foreach\s*\(((|[\s]+?)(.*?)(\s+)as(\s+)(.*?)(|[\s]+?)?)\).(.*?)@empty.(.*?)@endforeach#sim';
      $replacement  = "<?php if(!empty($3)): ?>\n";
      $replacement .= "<?php foreach($1): ?>\n";
      $replacement .= "$8";
      $replacement .= "<?php endforeach; ?>";
      $replacement .= "<?php else: ?>";
      $replacement .= "$9";
      $replacement .= "<?php endif; ?>";
      $this->view = preg_replace($pattern, $replacement, $this->view);
      /**
       * 
       * php for loop
       * 
       * menggantikan:
       * @for()     -> <?php for(): ?>
       * @endfor    -> <?php endfor; ?>
       * 
       */
      $pattern = '#@for\s*\((.*?)\).(.*?)@endfor#sim';
      $replacement = "<?php for($1): ?>$2\n<?php endfor; ?>";
      $this->view = preg_replace($pattern, $replacement, $this->view);
      /**
       * 
       * php while loop
       * 
       * menggantikan:
       * @while()     -> <?php while(): ?>
       * @endwhile    -> <?php endwhile; ?>
       * 
       */
      $pattern = '#@while\s*\((.*?)\).(.*?)@endwhile#sim';
      $replacement = "<?php while($1): ?>$2\n<?php endwhile; ?>";
      $this->view = preg_replace($pattern, $replacement, $this->view);
      /**
       * 
       * php do while loop
       * 
       * menggantikan:
       * @while()     -> <?php while(): ?>
       * @endwhile    -> <?php endwhile; ?>
       * 
       */
      $pattern = '#@do.(.*?)@while\s*\((.*?)\)#sim';
      $replacement = "<?php do { ?>$1\n<?php } while($2); ?>";
      $this->view = preg_replace($pattern, $replacement, $this->view);
   }

   /**
    * compiler untuk percabangan
    * @return void
    */
   protected function _compilerConditions(): void {
      /**
       * 
       * Mengambil text antara @if dan @endif
       * kemudian lakukan penyaringan kode dan
       * mengubahnya menjadi suatu baris program percabangan
       * 
      */    
      $this->view = preg_replace_callback("#@if\s*\((.*?)\)$(.*?)@endif$#sim", function($matches) {
         // Percabangan If
         if(!preg_match("#@else#sim", $matches[0])) {
            /**
             * percabangan if
             * 
             * menggantikan:
             * @if()       -> <?php if(): ?>
             * @endif      -> <?php endif; ?>
            */
            $pattern = '#@if\s*\((.*?)\).(.*?)@endif$#sim';
            $replacement = "<?php if($1): ?>\n";
            $replacement .= "$2";
            $replacement .= "<?php endif; ?>";
            $result = preg_replace($pattern, $replacement, $matches[0]);
            return $result;
         }
         // Percabangan If/Else
         elseif(preg_match("#@else$#sim", $matches[0]) && !preg_match("#@else\s*if#sim", $matches[0])) {
            /**
             * percabangan if/else
             * 
             * menggantikan:
             * @if()       -> <?php if(): ?>
             * @else       -> <?php else: ?>
             * @endif      -> <?php endif; ?>
            */
            $pattern = '#@if\s*\((.*)\).(.*?)@else.(.*)@endif#sim';
            $replacement = "<?php if($1): ?>\n";
            $replacement .= "$2";
            $replacement .= "<?php else: ?>\n";
            $replacement .= "$3";
            $replacement .= "<?php endif; ?>";
            $result = preg_replace($pattern, $replacement, $matches[0]);
            return $result;
         }
         // Percabangan if/elseif...elseif
         elseif(!preg_match("#@else$#sim", $matches[0]) && preg_match("#@else\s*if#sim", $matches[0])) {
            /**
             * percabangan if/elseif...elseif
             * 
             * menggantikan:
             * @if()       -> <?php if(): ?>
             * @elseif()       -> <?php elseif(): ?>
             * @endif      -> <?php endif; ?>
            */
            $pattern = '#@if\s*\((.*?)\).(.*?)(@else\s*if\s*.*?)@endif#sim';
            $replacement = function($matches) {
               // print_r($matches[3]);
               $pattern = '#@else\s*if\s*\((.*?)\).(.*?)#sim';
               $replacement = "<?php elseif($1): ?>\n";
               $elseif = preg_replace($pattern, $replacement, $matches[3]);
               
               $result = "<?php if($matches[1]): ?>\n$matches[2]";
               $result .= $elseif;
               $result .= "<?php endif; ?>";
               return $result;
            };
            $result = preg_replace_callback($pattern, $replacement, $matches[0]);
            return $result;
         }
         // percabangan if/elseif...elseif/else
         elseif(preg_match("#@else$#sim", $matches[0]) && preg_match("#@else\s*if#sim", $matches[0])) {
            /**
             * percabangan if/elseif..elseif/else
             * 
             * menggantikan:
             * @if()       -> <?php if(): ?>
             * @elseif()       -> <?php elseif(): ?>
             * @else       -> <?php else: ?>
             * @endif      -> <?php endif; ?>
            */
            $pattern = '#@if\s*\((.*?)\).(.*?)(@else\s*if.*?)@else$.(.*?)@endif#sim';
            $replacement = function($matches) {
               $pattern = '#@else\s*if\s*\((.*?)\).(.*?)#sim';
               $replacement = "<?php elseif($1): ?>\n";
               $elseif = preg_replace($pattern, $replacement, $matches[3]);

               $result = "<?php if($matches[1]): ?>\n$matches[2]";
               $result .= $elseif;
               $result .= "<?php else: ?>\n$matches[4]";
               $result .= "<?php endif; ?>";
               return $result;
            };
            $result = preg_replace_callback($pattern, $replacement, $matches[0]);
            return $result;
         }

      }, $this->view);
   }


   /**
    * Compiler untuk @extends
    * @return void
    */
   protected function _compilerExtends(): void {
      $pattern = "#@extends\(('|\")(.*?)('|\")\)#i";
      preg_match($pattern, $this->view, $matches);
      
      if(count($matches) > 0 && array_key_exists(2, $matches)) {
         $this->view = preg_replace($pattern, '', $this->view);
         $pattern = "#@extends\(('|\")(.*?)('|\")\)#i";

         $this->view .= PHP_EOL. $this->get_file($matches[2]);
         preg_match($pattern, $this->view, $matches);

         if(array_key_exists(2, $matches)) {

            $extends = $matches[2];
   
            if($extends == $this->extends) {
               throw new Exception("View: @extends('$extends') cannot call the file itself");
            }

            // digunakan untuk pengecekan apakah user memanggil file itu sendiri
            $this->extends = $extends;
            /**
             * Lakukan pengecekan secara recursive
             * dengan memanggil method ini sendiri
             */
            $this->{__FUNCTION__}();
         }
      }
   }

   /**
    * Compiler untuk @include
    * @return void
    */
   protected function _compilerInclude(): void {
      $pattern = "#@include\(('|\")(.*?)('|\")\)#i";
      preg_match_all($pattern, $this->view, $matches);
      foreach($matches[2] as $include) {
         // overwrite tag include
         $pattern_value = "#@include\(('|\")" . $include ."('|\")\)#i";
         $this->view = preg_replace($pattern_value,$this->get_file($include), $this->view);
         
         if($this->include == $include) {
            throw new Exception("View: @include('$include') cannot call the file itself");
         }


         // digunakan untuk pengecekan apakah user memanggil file itu sendiri
         $this->include = $include;
         /**
          * Lakukan pengecekan secara recursive
          * dengan memanggil method ini sendiri
          */
         $this->{__FUNCTION__}();
         
      }
   }

   /**
    * Compiler untuk @yield dan @section
    * @return void
    */
   protected function _compilerYieldSection(): void {
      $pattern = "#@yield\s*\(\s*('|\")(.*?)('|\")(\s*,\s*('|\")(.*?)('|\"))?\s*\)#sim";
      $calback = function($yield) {
         $yield_name = $yield[2];
         $pattern = "#@section\s*\(\s*('|\")" . $yield_name . "('|\")\s*\).(.*?)@endsection#sim";
         preg_match_all($pattern, $this->view, $section);

         if(count($section[3]) > 0) {
            $section = $section[3][0];
            return $section;
         } else {
            $pattern = "#@section\s*\(\s*('|\")" . $yield_name . "('|\")(\s*,\s*('|\")(.*?)('|\"))\s*\)$#sim";
            preg_match($pattern, $this->view, $section_default);

            if(count($section_default) > 0) {
               return $section_default[5];
            }
            elseif(array_key_exists(6, $yield)) {
               return $yield[6];
            } else {
               throw new Exception("View: " . $yield[0] . " has no default value!" );
            }
         }
      };

      // overwrite @yield('name') yang didapat dari @section('name')
      $this->view = preg_replace_callback($pattern, $calback, $this->view);
      // hapus tag @section
      $pattern    = "#@section\s*\(\s*('|\")(.*?)('|\")(,('|\")(.*?)('|\"))?\s*\).(.*?)@endsection#sim";
      $this->view = preg_replace($pattern, '', $this->view);
      $pattern    = "#@section\s*\(\s*('|\").*('|\")(\s*,\s*('|\")(.*?)('|\"))\s*\)$#sim";
      $this->view = preg_replace($pattern, '', $this->view);
   }


   /**
    * compiler Guardian
    * @return void
    */
   public function compile(): void {
      $this->_compilerExtends();
      $this->_compilerInclude();
      $this->_compilerYieldSection();

      $this->registerVariable();
      $this->_compilerPhp();
      $this->_compilerEchoPhp();
      $this->_compilerLoops();
      $this->_compilerConditions();
   }

   /**
    * output dari compiler
    */
   function html() {
      ob_start();
      try {
         eval("?>".$this->view);
      } catch (\ParseError $e) {
         ob_get_clean();
         return new \Exception($e->getMessage().': line '.$e->getLine());
      }
      $content = trim(ob_get_clean());

      return $content;
   }
}