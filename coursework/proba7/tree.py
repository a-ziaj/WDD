#!/usr/bin/env python3

import sys
import os
from Bio import AlignIO
from Bio.Phylo.TreeConstruction import DistanceCalculator, DistanceTreeConstructor
from Bio import Phylo
import matplotlib
matplotlib.use('Agg')  # For headless environments
import matplotlib.pyplot as plt

def plot_tree(tree, output_path, title):
    plt.figure(figsize=(12, 8 + (len(tree.get_terminals()) * 0.2)))
    axes = plt.gca()
    Phylo.draw(tree, axes=axes, label_func=lambda x: x.name if x.name else "",
              branch_labels=lambda x: "" if x.branch_length < 0.001 else round(x.branch_length, 2))
    plt.title(title)
    plt.savefig(output_path, bbox_inches='tight', dpi=300)
    plt.close()

def main(alignment_file, results_dir):
    # Load alignment
    try:
        alignment = AlignIO.read(alignment_file, "clustal")
    except Exception as e:
        print(f"Error reading alignment: {str(e)}")
        sys.exit(1)

    # Check sequence count
    if len(alignment) < 4:
        print("ERROR: Alignment contains fewer than 4 sequences")
        sys.exit(1)

    # Calculate distance matrix
    calculator = DistanceCalculator('blosum62')
    dm = calculator.get_distance(alignment)

    # Build trees
    constructor = DistanceTreeConstructor()

    # UPGMA Tree
    upgma_tree = constructor.upgma(dm)
    Phylo.write(upgma_tree, os.path.join(results_dir, "upgma.newick"), "newick")
    plot_tree(upgma_tree, os.path.join(results_dir, "upgma_tree.png"), "UPGMA Tree")

    # Neighbor-Joining Tree
    nj_tree = constructor.nj(dm)
    Phylo.write(nj_tree, os.path.join(results_dir, "nj.newick"), "newick")
    plot_tree(nj_tree, os.path.join(results_dir, "nj_tree.png"), "Neighbor-Joining Tree")

if __name__ == "__main__":
    if len(sys.argv) != 3:
        print("Usage: python tree.py <alignment_file> <output_dir>")
        sys.exit(1)
    main(sys.argv[1], sys.argv[2])
