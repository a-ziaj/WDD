import matplotlib.pyplot as plt
from collections import defaultdict

motifs = defaultdict(list)
current_seq = ''

with open('tmp/job_67e5b4042f2c0_motifs/prosite_results.txt') as f:
    for line in f:
        if line.startswith('>>'):
            current_seq = line[2:].strip()
        elif line.startswith('Pattern'):
            parts = line.split()
            motifs[current_seq].append((int(parts[3]), int(parts[4]), parts[1]))

plt.figure(figsize=(12, max(6, len(motifs)*0.5)))
for i, (seq, hits) in enumerate(motifs.items()):
    seq_length = max(h[1] for h in hits) if hits else 100
    plt.plot([0, seq_length + 50], [i, i], 'k-', alpha=0.3)
    for start, end, name in hits:
        plt.plot([start, end], [i, i], lw=10, label=name)
        plt.text((start+end)/2, i+0.2, name, ha='center', fontsize=8)

plt.yticks(range(len(motifs)), list(motifs.keys()))
plt.xlabel('Position')
plt.title('PROSITE Motif Distribution')
plt.tight_layout()
plt.savefig('tmp/job_67e5b4042f2c0_motifs/motif_map.png', dpi=150, bbox_inches='tight')