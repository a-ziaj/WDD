#!/usr/bin/env python3

from Bio.Phylo.TreeConstruction import DistanceTreeConstructor, DistanceMatrix
from Bio import Phylo
import matplotlib.pyplot as plt

# Helper function to safely convert to float
def is_float(s):
    try:
        float(s)
        return True
    except ValueError:
        return False
def read_distance_matrix(file_path):
    with open(file_path, 'r') as f:
        lines = [line.strip() for line in f if line.strip()]

    labels = []
    matrix = []

    for i, line in enumerate(lines):
        parts = line.split()[:-1]  # strip the index number at end
        label = parts[-1]
        labels.append(label)

        numeric_values = [float(x) for x in parts[:-1] if is_float(x)]  # strip label
        if len(numeric_values) < i + 1:
            raise ValueError(f"Row {i+1} is too short: expected {i+1} numbers, got {len(numeric_values)}")
        values = numeric_values[:i+1]
        matrix.append(values)

    print("Labels:", labels)
    print("Lower triangular matrix:")
    for row in matrix:
        print(row)

    return DistanceMatrix(names=labels, matrix=matrix)


# MAIN
dm = read_distance_matrix("dist_mat.mat")
print(dm)
constructor = DistanceTreeConstructor()
tree = constructor.nj(dm)

# Save Newick file
Phylo.write(tree, "nj_tree.nwk", "newick")

# Save PNG tree image
plt.figure(figsize=(10, 5))
Phylo.draw(tree, do_show=False)
plt.savefig("nj_tree.png", format="png")
plt.close()

print("NJ tree saved as nj_tree.nwk and nj_tree.png")



