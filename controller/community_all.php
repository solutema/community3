<?php

/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2015-2016  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once __DIR__.'/community_home.php';

/**
 * Description of community_home
 *
 * @author carlos
 */
class community_all extends community_home
{
   public $mostrar;
   public $num_pendientes;
   public $offset;
   public $resultados;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Todo', 'comunidad');
   }
   
   protected function private_core()
   {
      parent::private_core();
      
      $this->mostrar = 'todo';
      if( isset($_GET['mostrar']) )
      {
         $this->mostrar = $_GET['mostrar'];
      }
      
      $this->offset = 0;
      if( isset($_GET['offset']) )
      {
         $this->offset = intval($_GET['offset']);
         if($this->offset < 0)
         {
            $this->offset = 0;
         }
      }
      
      $item = new comm3_item();
      $this->num_pendientes = $item->num_pendientes($this->user->nick, $this->user->admin);
      
      if($this->mostrar == 'pendiente')
      {
         $this->resultados = $item->pendientes($this->offset, $this->user->nick, $this->user->admin);
      }
      else if($this->mostrar == 'mix')
      {
         $this->resultados = array();
         $emails = array();
         $continuar = TRUE;
         while( $continuar AND count($this->resultados) < FS_ITEM_LIMIT )
         {
            $continuar = FALSE;
            foreach($item->pendientes($this->offset, $this->user->nick, $this->user->admin) as $res)
            {
               if( !in_array($res->email(), $emails) )
               {
                  $this->resultados[] = $res;
                  $emails[] = $res->email();
               }
               
               $this->offset++;
               $continuar = TRUE;
            }
         }
      }
      else
         $this->resultados = $item->all($this->offset);
   }
   
   protected function public_core()
   {
      parent::public_core();
      
      $this->page_title = 'Todo &lsaquo; Comunidad FacturaScripts';
      $this->page_description = 'Todas las preguntas, ideas e informes de errores de FacturaScripts';
      $this->page_keywords = 'foro FacturaScripts, ayuda FacturaScripts';
      $this->template = 'public/all';
      
      $this->offset = 0;
      if( isset($_GET['offset']) )
      {
         $this->offset = intval($_GET['offset']);
      }
      
      $this->mostrar = 'todo';
      if( isset($_GET['mostrar']) )
      {
         $this->mostrar = $_GET['mostrar'];
      }
      
      /// mostramos los resultados
      $this->resultados = array();
      $sql = "SELECT * FROM comm3_items ";
      if($this->visitante)
      {
         if($this->mostrar == 'mio')
         {
            $sql .= "WHERE email = ".$this->empresa->var2str($this->visitante->email);
         }
         else if($this->mostrar == 'codpais')
         {
            $sql .= "WHERE privado = false AND codpais = ".$this->empresa->var2str($this->visitante->codpais);
         }
         else /// todo
         {
            $sql .= "WHERE privado = false OR email = ".$this->empresa->var2str($this->visitante->email);
         }
      }
      else /// todo
      {
         $sql .= "WHERE privado = false";
      }
      $sql .= " ORDER BY actualizado DESC";
      
      $data = $this->db->select_limit($sql, FS_ITEM_LIMIT, $this->offset);
      if($data)
      {
         foreach($data as $d)
         {
            $this->resultados[] = new comm3_item($d);
         }
      }
   }
   
   public function anterior_url()
   {
      $url = '';
      
      if($this->offset > 0)
      {
         $url = $this->url()."&offset=".($this->offset-FS_ITEM_LIMIT).'&mostrar='.$this->mostrar;
      }
      
      return $url;
   }
   
   public function siguiente_url()
   {
      $url = '';
      
      if( count($this->resultados) >= FS_ITEM_LIMIT )
      {
         $url = $this->url()."&offset=".($this->offset+FS_ITEM_LIMIT).'&mostrar='.$this->mostrar;
      }
      
      return $url;
   }
}
