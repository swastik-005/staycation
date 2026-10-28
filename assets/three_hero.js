/**
 * Mara Voss Aesthetic - Interactive Three.js Ambient Canvas
 * Procedural wireframe wave & particle constellation responding to mouse movement.
 */

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('three-hero-container');
    if (!container || typeof THREE === 'undefined') return;

    let scene, camera, renderer, particles, mesh;
    let mouseX = 0, mouseY = 0;
    let targetX = 0, targetY = 0;
    const windowHalfX = window.innerWidth / 2;
    const windowHalfY = window.innerHeight / 2;

    function init() {
        // Scene setup
        scene = new THREE.Scene();
        scene.fog = new THREE.FogExp2(0x0a0a0c, 0.0018);

        // Camera setup
        camera = new THREE.PerspectiveCamera(55, container.clientWidth / container.clientHeight, 1, 3000);
        camera.position.z = 1000;
        camera.position.y = 200;

        // 1. Procedural Luxury Wave Grid
        const planeGeo = new THREE.PlaneGeometry(2400, 1600, 48, 36);
        const planeMat = new THREE.MeshBasicMaterial({
            color: 0xc5a880,
            wireframe: true,
            transparent: true,
            opacity: 0.12,
        });

        mesh = new THREE.Mesh(planeGeo, planeMat);
        mesh.rotation.x = -Math.PI / 2.3;
        mesh.position.y = -220;
        scene.add(mesh);

        // 2. Ambient Constellation Particles
        const particleCount = 450;
        const geometry = new THREE.BufferGeometry();
        const positions = new Float32Array(particleCount * 3);
        const colors = new Float32Array(particleCount * 3);

        const goldColor = new THREE.Color(0xdfba89);
        const dimGold = new THREE.Color(0x75634d);

        for (let i = 0; i < particleCount * 3; i += 3) {
            positions[i] = (Math.random() - 0.5) * 2000;
            positions[i + 1] = Math.random() * 800 - 150;
            positions[i + 2] = (Math.random() - 0.5) * 1500;

            const mixedColor = dimGold.clone().lerp(goldColor, Math.random());
            colors[i] = mixedColor.r;
            colors[i + 1] = mixedColor.g;
            colors[i + 2] = mixedColor.b;
        }

        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));

        const particleMaterial = new THREE.PointsMaterial({
            size: 3.5,
            vertexColors: true,
            transparent: true,
            opacity: 0.75,
            blending: THREE.AdditiveBlending
        });

        particles = new THREE.Points(geometry, particleMaterial);
        scene.add(particles);

        // Renderer
        renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true, powerPreference: 'high-performance' });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        renderer.setSize(container.clientWidth, container.clientHeight);
        renderer.setClearColor(0x000000, 0);
        container.appendChild(renderer.domElement);

        // Listeners
        window.addEventListener('resize', onWindowResize, false);
        document.addEventListener('mousemove', onDocumentMouseMove, false);
    }

    function onWindowResize() {
        if (!container) return;
        camera.aspect = container.clientWidth / container.clientHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(container.clientWidth, container.clientHeight);
    }

    function onDocumentMouseMove(event) {
        mouseX = (event.clientX - windowHalfX) * 0.4;
        mouseY = (event.clientY - windowHalfY) * 0.4;
    }

    let clock = new THREE.Clock();

    function animate() {
        requestAnimationFrame(animate);

        const elapsedTime = clock.getElapsedTime();

        // Wave animation
        if (mesh && mesh.geometry) {
            const positionAttribute = mesh.geometry.attributes.position;
            for (let i = 0; i < positionAttribute.count; i++) {
                const u = positionAttribute.getX(i);
                const v = positionAttribute.getY(i);
                const z = Math.sin(u * 0.003 + elapsedTime * 0.9) * 45 +
                          Math.cos(v * 0.004 + elapsedTime * 0.7) * 40;
                positionAttribute.setZ(i, z);
            }
            positionAttribute.needsUpdate = true;
        }

        // Particle subtle drift
        if (particles) {
            particles.rotation.y = elapsedTime * 0.03;
        }

        // Smooth camera follow mouse
        targetX = mouseX * 0.5;
        targetY = mouseY * 0.3;

        camera.position.x += (targetX - camera.position.x) * 0.04;
        camera.position.y += (-targetY + 200 - camera.position.y) * 0.04;
        camera.lookAt(new THREE.Vector3(0, 0, 0));

        renderer.render(scene, camera);
    }

    init();
    animate();
});

