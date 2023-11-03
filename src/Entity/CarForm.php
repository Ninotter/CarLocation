<?php
namespace App\Entity;

class CarForm{
    private $nom;
    private $prenom;
    private $age;
    private $ville;
    private $vehicule;
    private $token;

    public function getNom(){
        return $this->nom;
    }
    public function setNom($nom){
        $this->nom = $nom;
        return $this;
    }
    public function getPrenom(){
        return $this->prenom;
    }
    public function setPrenom($prenom){
        $this->prenom = $prenom;
        return $this;
    }
    public function getAge(){
        return $this->age;
    }
    public function setAge($age){
        $this->age = $age;
        return $this;
    }
    public function getVille(){
        return $this->ville;
    }
    public function setVille($ville){
        $this->ville = $ville;
        return $this;
    }
    public function getVehicule(){
        return $this->vehicule;
    }
    public function setVehicule($vehicule){
        $this->vehicule = $vehicule;
        return $this;
    }
    public function getToken(){
        return $this->token;
    }
    public function setToken($token){
        $this->token = $token;
        return $this;
    }
}