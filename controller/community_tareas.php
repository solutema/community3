<?php

/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2015  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_model('comm3_item.php');

/**
 * Description of community_tareas
 *
 * @author carlos
 */
class community_tareas extends fs_controller
{
   public $mostrar;
   public $perfil;
   public $resultados;
   
   private $offset;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Tareas', 'comunidad');
   }
   
   protected function private_core()
   {
      $this->perfil = comm3_get_perfil_user($this->user);
      
      $this->offset = 0;
      if( isset($_GET['offset']) )
      {
         $this->offset = intval($_GET['offset']);
      }
      
      $this->mostrar = 'pendientes';
      if( isset($_GET['mostrar']) )
      {
         $this->mostrar = $_GET['mostrar'];
      }
      
      $item = new comm3_item();
      if($this->mostrar == 'pendientes')
      {
         $this->resultados = $item->pendientes($this->offset, $this->user->nick, $this->user->admin, 'task');
      }
      else if($this->mostrar == 'tuyo')
      {
         $this->resultados = $item->all_by_nick($this->user->nick, $this->offset, 'task');
      }
      else
         $this->resultados = $item->all_by_tipo('task', $this->offset);
   }
   
   protected function public_core()
   {
      
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
      
      if( count($this->resultados) == FS_ITEM_LIMIT )
      {
         $url = $this->url()."&offset=".($this->offset+FS_ITEM_LIMIT).'&mostrar='.$this->mostrar;
      }
      
      return $url;
   }
   
   public function num_pendientes($only_public = FALSE)
   {
      if($only_public)
      {
         $total = 0;
         $sql = "SELECT COUNT(*) as total FROM comm3_items WHERE tipo = 'task' AND (estado != 'cerrado' OR estado is NULL) AND privado = false";
         $data = $this->db->select($sql);
         if($data)
         {
            $total = intval($data[0]['total']);
         }
         return $total;
      }
      else
      {
         $item = new comm3_item();
         return $item->num_pendientes($this->user->nick, $this->user->admin, 'task');
      }
   }
}