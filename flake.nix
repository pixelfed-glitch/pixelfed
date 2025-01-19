{
  description = "Dev Environment flake";

  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixos-unstable";
  };
  outputs = { self, nixpkgs }:

  let
      system = "x86_64-linux";
      pkgs = nixpkgs.legacyPackages.${system};
  in {
    packages.x86_64-linux.default = import ./shell.nix { inherit pkgs; };
  };
}
