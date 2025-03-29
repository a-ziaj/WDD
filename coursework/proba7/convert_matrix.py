#!/usr/bin/env python3
import re

def parse_distance_matrix(file_path):
    with open(file_path, 'r') as f:
        lines = [line.rstrip() for line in f if line.strip()]
    
    # Find where the matrix data starts
    data_start = 0
    for i, line in enumerate(lines):
        if line.startswith('          1'):
            data_start = i + 1
            size = len(line.split()) - 1  # Number of sequences
            break
    
    # Initialize distance matrix
    dist_matrix = [[0.0] * size for _ in range(size)]
    
    # Parse each line of the matrix
    for i in range(size):
        line = lines[data_start + i]
        
        # Split into components
        parts = re.split(r'\s+', line.strip())
        
        # Extract distances (numeric parts before sequence name)
        distances = []
        for part in parts:
            if re.match(r'^-?\d+\.\d+$', part):
                distances.append(float(part))
            elif part and part[0].isalpha():  # Reached sequence name
                break
        
        # Fill the matrix row
        for j in range(i + 1):
            if j == i:  # Diagonal element
                dist_matrix[i][j] = 0.0
            else:
                dist_matrix[i][j] = distances[j]
    
    # Fill upper triangle (symmetric matrix)
    for i in range(size):
        for j in range(i + 1, size):
            dist_matrix[i][j] = dist_matrix[j][i]
    
    return dist_matrix

def convert_to_lower_triangular(dist_matrix):
    return [dist_matrix[i][:i+1] + [0.0] for i in range(len(dist_matrix))]

def print_matrices(original, lower_tri):
    print("Original Distance Matrix:")
    for row in original:
        print([f"{x:.2f}" for x in row])
    
    print("\nLower Triangular Format for Biopython:")
    print("[")
    for i, row in enumerate(lower_tri):
        formatted_row = [f"{x:.2f}" if isinstance(x, float) else str(x) for x in row]
        if i < len(lower_tri) - 1:
            print(f"    [{', '.join(formatted_row)}],")
        else:
            print(f"    [{', '.join(formatted_row)}]")
    print("]")

if __name__ == "__main__":
    input_file = "dist_mat.mat"
    
    # Parse the original matrix
    original_matrix = parse_distance_matrix(input_file)
    
    # Convert to lower triangular format
    lower_tri_matrix = convert_to_lower_triangular(original_matrix)
    
    # Print both matrices
    print_matrices(original_matrix, lower_tri_matrix)
