#!/bin/bash

FASTA_FILE="$1"
RESULTS_DIR="$2"

# Validate input file
if [ ! -f "$FASTA_FILE" ]; then
    echo "ERROR: FASTA file not found: $FASTA_FILE" >&2
    exit 1
fi

# Check minimum sequence count
SEQ_COUNT=$(grep -c '^>' "$FASTA_FILE")
if [ "$SEQ_COUNT" -lt 4 ]; then
    echo "ERROR: Insufficient sequences ($SEQ_COUNT). Need at least 4." >&2
    exit 1
fi

# Create multiple sequence alignment
ALIGNMENT_FILE="$RESULTS_DIR/alignment.clustal"
clustalo -i "$FASTA_FILE" -o "$ALIGNMENT_FILE" --force --threads=2 --outfmt=clustal

# Check alignment success
if [ ! -s "$ALIGNMENT_FILE" ]; then
    echo "ERROR: Multiple sequence alignment failed" >&2
    exit 1
fi

# Generate phylogenetic trees
python3 tree.py "$ALIGNMENT_FILE" "$RESULTS_DIR"

exit $?
