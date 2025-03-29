#!/usr/bin/env python3
import sys
from Bio.Phylo.TreeConstruction import DistanceMatrix, DistanceTreeConstructor
from Bio import Phylo
import matplotlib.pyplot as plt
def parse_dist_matrix(file):
    with open(file) as f:
        lines = [line.rstrip() for line in f if line.strip() and not line.startswith('#')]

    # Skip header lines
    matrix_lines = []
    in_matrix = False
    for line in lines:
        if line.startswith("Distance Matrix"):
            in_matrix = True
            continue
        if in_matrix and not (line.startswith("---") or line.startswith("Using the") or line.startswith("Gap")):
            if not all(x.isdigit() for x in line.split()):  # Skip column number lines
                matrix_lines.append(line)

    print("\nRaw matrix lines:")
    for i, line in enumerate(matrix_lines):
        print(f"Line {i+1}: {line}")

    # Parse names and distances
    names = []
    distances = []

    for line in matrix_lines:
        parts = line.split()
        # The name is the last non-numeric part
        name = None
        dist_values = []
        
        for part in parts:
            if part.replace('.', '').replace('-', '').isdigit():
                dist_values.append(float(part))
            else:
                name = part
        
        # If no name found (all numeric), use the last distance as identifier
        if name is None and dist_values:
            name = str(dist_values[-1])
        
        names.append(name)
        distances.append(dist_values)

    print("\nParsed names and distances:")
    for name, dist in zip(names, distances):
        print(f"{name}: {dist}")

    # Create lower triangular matrix
    lower_tri = []
    for i in range(len(distances)):
        # For row i, we need the first i elements (excluding diagonal)
        row = distances[i][:i]
        lower_tri.append(row)

    print("\nLower triangular matrix:")
    for i, row in enumerate(lower_tri):
        print(f"Row {i+1} ({names[i]}): {row}")

    # Verify matrix structure
    expected_lengths = list(range(len(names)))
    actual_lengths = [len(row) for row in lower_tri]
    if actual_lengths != expected_lengths:
        print("\nMatrix Structure Error:")
        print(f"Expected row lengths: {expected_lengths} (for {len(names)} taxa)")
        print(f"Actual row lengths:   {actual_lengths}")
        print("\nFull distance matrix before triangular conversion:")
        for i, row in enumerate(distances):
            print(f"Row {i+1}: {row}")
        raise ValueError(f"Matrix format error. Expected row lengths {expected_lengths}, got {actual_lengths}")

    return DistanceMatrix(names, lower_tri)def parse_dist_matrix(file):
    with open(file) as f:
        lines = [line.rstrip() for line in f if line.strip() and not line.startswith('#')]

    # Skip header lines
    matrix_lines = []
    in_matrix = False
    for line in lines:
        if line.startswith("Distance Matrix"):
            in_matrix = True
            continue
        if in_matrix and not (line.startswith("---") or line.startswith("Using the") or line.startswith("Gap")):
            if not all(x.isdigit() for x in line.split()):  # Skip column number lines
                matrix_lines.append(line)

    print("\nRaw matrix lines:")
    for i, line in enumerate(matrix_lines):
        print(f"Line {i+1}: {line}")

    # Parse names and distances
    names = []
    distances = []

    for line in matrix_lines:
        parts = line.split()
        # The name is the last non-numeric part
        name = None
        dist_values = []
        
        for part in parts:
            if part.replace('.', '').replace('-', '').isdigit():
                dist_values.append(float(part))
            else:
                name = part
        
        # If no name found (all numeric), use the last distance as identifier
        if name is None and dist_values:
            name = str(dist_values[-1])
        
        names.append(name)
        distances.append(dist_values)

    print("\nParsed names and distances:")
    for name, dist in zip(names, distances):
        print(f"{name}: {dist}")

    # Create lower triangular matrix
    lower_tri = []
    for i in range(len(distances)):
        # For row i, we need the first i elements (excluding diagonal)
        row = distances[i][:i]
        lower_tri.append(row)

    print("\nLower triangular matrix:")
    for i, row in enumerate(lower_tri):
        print(f"Row {i+1} ({names[i]}): {row}")

    # Verify matrix structure
    expected_lengths = list(range(len(names)))
    actual_lengths = [len(row) for row in lower_tri]
    if actual_lengths != expected_lengths:
        print("\nMatrix Structure Error:")
        print(f"Expected row lengths: {expected_lengths} (for {len(names)} taxa)")
        print(f"Actual row lengths:   {actual_lengths}")
        print("\nFull distance matrix before triangular conversion:")
        for i, row in enumerate(distances):
            print(f"Row {i+1}: {row}")
        raise ValueError(f"Matrix format error. Expected row lengths {expected_lengths}, got {actual_lengths}")

    return DistanceMatrix(names, lower_tri)
