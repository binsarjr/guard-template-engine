<?php


namespace Intersec\View;

use Exception;

class Guardian {
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
   protected string $view_path;
   /**
    * ekstensi yang digunakan pada template engine
    */
   protected string $ext = '.guard.php';

   /**
    * Mempersiapkan semuanya terlebih
    * dahulu sebelum proses dijalankan
    * 
    * @param String $file
    * @param String $view_path
    *
    */
   public function __construct(String $file, String $view_path = '') {
      // cek apakah file sudah tersedia
      if(!file_exists($file)) {
         throw new Exception("View: [" . basename($file) . "] does not exists!");
      }
      // cek jika ekstensi sama dengan property ext
      if(substr(strtolower(basename($file)), -strlen($this->ext)) !== $this->ext) {
         throw new Exception("View: [" . basename($file) . "] invalid extension!");
      }
      $this->view_path = $view_path;
      $this->view = file_get_contents($file);
   }
   public function set($key, $values): void {
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
            $view = $view . "$" . $key . ' = json_decode(json_encode(\''. $value .'\'));'.PHP_EOL;
         } else {
            $view = $view . "$" . $key . ' = json_decode(\''. $value .'\');'.PHP_EOL;
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
   protected function _compilerRawPhp(): void {
      $pattern = "#{{(?:(|\s)+)(.*?)(?:(|\s)+)}}#sim";
      $replacement = '<?= $2; ?>';
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
      $replacement  = "<?php foreach($1): ?>$2<?= PHP_EOL ?><?php endforeach; ?>";
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
      $replacement  = "<?php if(!empty($3)): ?>".PHP_EOL;
      $replacement .= "<?php foreach($1): ?>".PHP_EOL;
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
      $replacement = "<?php for($1): ?>$2<?= PHP_EOL ?><?php endfor; ?>";
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
      $replacement = "<?php while($1): ?>$2<?= PHP_EOL ?><?php endwhile; ?>";
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
      $replacement = "<?php do { ?>$1<?= PHP_EOL ?><?php } while($2); ?>";
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
    * compiler Guardian
    * @return void
    */
   public function compile(): void {
      $this->registerVariable();
      $this->_compilerPhp();
      $this->_compilerRawPhp();
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
      $content = ob_get_clean();
      return $content;
   }
}

$guard = new Guardian('Template.guard.php');
$guard->set('cobain',"ok'e");
$guard->set('cobain1',1);
$guard->set('cobain2',['binsar']);
$guard->compile();
// echo $guard->view;
$ok = $guard->html();
echo $ok;