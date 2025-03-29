#!/bin/bash

FASTA_FILE="$1"
OUTPUT_DIR="$2"

mkdir -p "$OUTPUT_DIR"

# Example Foldseek command - adjust as needed
foldseek easy-search "$FASTA_FILE" /path/to/database "$OUTPUT_DIR/results.tsv" tmp --format-output query,target,pident,alnlen,bit

