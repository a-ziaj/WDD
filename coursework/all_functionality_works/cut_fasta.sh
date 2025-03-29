#!/bin/bash

# cut_fasta.sh - Extract first N sequences from a FASTA file

# Usage: ./cut_fasta.sh input.fasta output.fasta N

INPUT_FASTA="$1"
OUTPUT_FASTA="$2"
NUM_SEQUENCES="$3"

# Verify arguments
if [ $# -ne 3 ]; then
    echo "Usage: $0 input.fasta output.fasta num_sequences" >&2
    exit 1
fi

if [ ! -f "$INPUT_FASTA" ]; then
    echo "Error: Input file $INPUT_FASTA not found" >&2
    exit 1
fi

if ! [[ "$NUM_SEQUENCES" =~ ^[0-9]+$ ]]; then
    echo "Error: num_sequences must be a positive integer" >&2
    exit 1
fi

# Use awk to extract first N sequences
awk -v n="$NUM_SEQUENCES" '
    /^>/ { if (++count > n) exit; print; next }
    count > 0 && count <= n { print }
' "$INPUT_FASTA" > "$OUTPUT_FASTA"

# Verify output
if [ ! -s "$OUTPUT_FASTA" ]; then
    echo "Error: No sequences were extracted - check your input file" >&2
    exit 1
fi

echo "Successfully extracted first $NUM_SEQUENCES sequences to $OUTPUT_FASTA"
exit 0
