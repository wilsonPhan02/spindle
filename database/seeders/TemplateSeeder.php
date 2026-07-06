<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Template;

class TemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // =========================================================
        // 1. Three Act Structure (System Template)
        // =========================================================

        $threeAct = Template::create([
            'user_id' => null, 
            'name' => 'Three-Act Structure',
            'description' => <<<'MARKDOWN'
            The Three-Act Structure is the most classic and widely used narrative model in screenwriting, novel writing, and even oral storytelling. It divides a story into three distinct parts: the Setup, the Confrontation, and the Resolution.

            Think of it like a bridge: Act 1 is the solid foundation on one side, Act 2 is the long, winding path over the water, and Act 3 is the landing on the other side—only this time, the traveler is forever changed.
            MARKDOWN,
            'image_preview' => 'structures.three-act',
            'is_custom' => false,
        ]);

        $threeAct->sections()->createMany([
            [
                'order_index' => 1,
                'title' => 'Act I: The Setup',
                'goal' => <<<'MARKDOWN'
                Duration: Approximately 25% of the story.

                Formally, this is the Exposition phase. This is where the audience is introduced to the story's world, the main character (the protagonist), and the rules of the universe.

                * **The Status Quo (Ordinary Life):** Simply put, this is the "Once upon a time..." moment. We see the character’s daily life before everything gets flipped upside down.
                * **Inciting Incident (The Catalyst):** This is the "But one day..." moment. An event occurs that disrupts the protagonist’s comfort zone and demands action. Example: Katniss Everdeen volunteering for the Hunger Games.
                * **Plot Point 1:** This is the exit door from Act 1. The character makes a conscious decision to accept the challenge. Once they pass this point, there is no going back to their old life.
                MARKDOWN
            ],
            [
                'order_index' => 2,
                'title' => 'Act II: The Confrontation',
                'goal' => <<<'MARKDOWN'
                Duration: Approximately 50% of the story (The longest part).

                Formally known as the Rising Action. The protagonist tries to solve the problem, but obstacles and complications keep stacking up.
                * **Fun and Games (Tests & Obstacles):** This is where the character learns to "swim" in the new world. They meet allies, encounter enemies, and face minor hurdles. Easy explanation: This is the "meat" of the story or the core adventure.
                * **The Midpoint:** A major event in the dead center of the story where the stakes suddenly skyrocket. The character shifts from being "reactive" (just surviving) to "proactive" (actually taking the fight to the problem).
                * **All is Lost & The Dark Night of the Soul:** Toward the end of Act 2, the character usually suffers a crushing defeat. They feel like a failure and want to give up.
                * **Plot Point 2:** A new piece of information or a surge of motivation that forces the character to pick themselves up for the final fight.
                MARKDOWN
            ],
            [
                'order_index' => 3,
                'title' => 'Act III: The Resolution',
                'goal' => <<<'MARKDOWN'
                Duration: Approximately 25% of the story.

                This is the Conclusion. All the tension built since Act 1 must be paid off here.
                * **The Climax:** The "Final Boss Battle." This is the peak of the conflict. The protagonist must use everything they’ve learned to defeat the antagonist or resolve their internal struggle.
                * **The Resolution/Denouement:** After the storm passes, we see how the dust settles. The tension is released, and the subplots are tied up.
                * **New Status Quo:** The new life established after the conflict. The world has changed, and the protagonist has undergone a "Character Arc" (transformation).
                MARKDOWN
            ]
        ]);

        // =========================================================
        // 2. Dan's Harmon Story Circle Structure (System Template)
        // =========================================================

        $storyCircle = Template::create([
            'user_id' => null,
            'name' => 'Dan Harmon\'s Story Circle',
            'description' => <<<'MARKDOWN'
            Dan Harmon’s Story Circle (also known as the Plot Embryo) is a streamlined, eight-step iteration of the "Hero’s Journey." Designed by the creator of Community and Rick and Morty, it ensures that a story remains focused on character evolution through a rhythmic cycle of order and chaos.

            Imagine it like a revolving door: You start in the light of the familiar, push through into the darkness of the unknown to get what you need, and swing back out into the light—but you are no longer the same person who walked in.
            MARKDOWN,
            'image_preview' => 'structures.dan-harmons-story-circle',
            'is_custom' => false,
        ]);

        $storyCircle->sections()->createMany([
            [
                'order_index' => 1,
                'title' => 'Zone of Comfort',
                'goal' => <<<'MARKDOWN'
                This is the introduction to the protagonist in their "Ordinary World." It establishes the status quo—how they live, what they do, and who they think they are. While they are safe and comfortable, there is usually a subtle hint of stagnation or a latent flaw. For a writer, this step is about making the audience care about the character before their life is disrupted.
                MARKDOWN
            ],
            [
                'order_index' => 2,
                'title' => 'Need or Desire',
                'goal' => <<<'MARKDOWN'
                The character realizes something is missing. This could be an external lack (money, safety, a missing person) or an internal void (validation, purpose, love). This "need" creates a specific goal. It is the engine of the story; without a clear desire, the character has no reason to move. This is the moment the character realizes that their current "Zone of Comfort" is no longer enough.
                MARKDOWN
            ],
            [
                'order_index' => 3,
                'title' => 'Crossing a Threshold',
                'goal' => <<<'MARKDOWN'
                The protagonist makes a conscious choice to leave their familiar surroundings and step into the "Unknown World." This is the point of no return. Whether they are literally traveling to a new land or metaphorically entering a new situation (like a first day at a high-stakes job), they have crossed the line from Order into Chaos. The story officially shifts gears here.
                MARKDOWN
            ],
            [
                'order_index' => 4,
                'title' => 'Road of Trials',
                'goal' => <<<'MARKDOWN'
                The protagonist begins to understand the rules, culture, and challenges of the new world they have entered. They encounter a series of increasingly difficult obstacles that test their skills, beliefs, and determination. Along the way, they make mistakes, experience failures, and sometimes suffer losses. Through these trials, they gradually become stronger, gain new allies, and develop the knowledge and confidence needed to move closer to their goal.
                MARKDOWN
            ],
            [
                'order_index' => 5,
                'title' => 'Get What They Wanted',
                'goal' => <<<'MARKDOWN'
                After enduring many hardships, the protagonist finally achieves the goal they have been pursuing or obtains the object of their desire. This victory often comes at a significant cost, such as sacrificing relationships, losing something valuable, or compromising part of themselves. At this point, they may also realize that what they wanted is different from what they truly needed, leading to a deeper understanding of themselves.
                MARKDOWN
            ],
            [
                'order_index' => 6,
                'title' => 'Pay a Heavy Price',
                'goal' => <<<'MARKDOWN'
                The consequences of achieving the goal become clear. The protagonist must face the emotional, physical, or moral costs of their actions and decisions. This stage often represents the lowest point of the story, where they question whether the journey was worth it. They may lose loved ones, face betrayal, or experience a major setback that forces them to confront their greatest fears and flaws.
                MARKDOWN
            ],
            [
                'order_index' => 7,
                'title' => 'Return to Comfort',
                'goal' => <<<'MARKDOWN'
                Having overcome the central conflict, the protagonist returns to their ordinary world or finds a new place they can call home. They bring back the wisdom, experience, or rewards gained during their journey. Although they are physically back in familiar surroundings, their perspective has changed, allowing them to see their old life in a new light and often positively influence the people around them.
                MARKDOWN
            ],
            [
                'order_index' => 8,
                'title' => 'Having Changed',
                'goal' => <<<'MARKDOWN'
                The protagonist is no longer the same person who began the journey. They have grown emotionally, mentally, or spiritually through the challenges they faced. The lessons they learned reshape their values, relationships, and outlook on life. Even if their external circumstances appear similar to the beginning of the story, their internal transformation demonstrates that the journey has permanently changed who they are.
                MARKDOWN
            ],
        ]);

        // =========================================================
        // 3. Freytag's Comandment Structure (System Template)
        // =========================================================

        
        $freytags = Template::create([
            'user_id' => null, 
            'name' => 'Freytag\'s Pyramid',
            'description' => <<<'MARKDOWN'
            Freytag’s Pyramid is one of the oldest and most fundamental frameworks in literary analysis, developed by 19th-century German playwright Gustav Freytag. Originally designed to map the structure of five-act Greek and Shakespearean tragedies, it remains a vital tool for writers who want to understand the rise and fall of dramatic tension.

            Think of this structure as a mountain climb. You spend the first half of the journey ascending toward a singular, life-changing peak, and the second half dealing with the momentum of the descent—where every choice made on the way up determines whether you land safely or crash at the bottom.
            MARKDOWN,
            'image_preview' => 'structures.freytags',
            'is_custom' => false,
        ]);

        $freytags->sections()->createMany([
            [
                'order_index' => 1,
                'title' => 'Exposition',
                'goal' => <<<'MARKDOWN'
                This is the foundation of the story. The writer introduces the setting, the atmosphere, and the primary characters. It provides the essential "backstory" and context needed for the audience to understand the stakes. For a writer, the goal here is to establish the status quo so clearly that the audience recognizes exactly what is being threatened when the conflict begins.
                MARKDOWN
            ],
            [
                'order_index' => 2,
                'title' => 'Rising Action',
                'goal' => <<<'MARKDOWN'
                This phase begins with an inciting incident that triggers a series of events. As the protagonist moves toward their goal, complications arise, and the tension builds steadily. Each "step" up the pyramid represents a complication that makes the conflict more intense and the stakes higher. For the writer, this is about creating momentum—ensuring that the protagonist’s choices lead to increasingly difficult situations that they cannot escape.
                MARKDOWN
            ],
            [
                'order_index' => 3,
                'title' => 'Climax',
                'goal' => <<<'MARKDOWN'
                In Freytag’s Pyramid, the climax is not necessarily the "final battle," but rather the turning point. It is the highest point of the pyramid where the fortunes of the protagonist change. In a tragedy, this is where things start to go wrong; in a comedy, this is where they start to go right. It is the moment of maximum tension where the protagonist’s path is set in stone, and the final outcome becomes inevitable. A writer must ensure this moment feels like a direct consequence of everything that happened during the Rising Action.
                MARKDOWN
            ],
            [
                'order_index' => 4,
                'title' => 'Falling Action',
                'goal' => <<<'MARKDOWN'
                This is the aftermath of the climax. The consequences of the protagonist's turning point begin to play out, and the "knot" of the story starts to unravel. The tension doesn't necessarily disappear, but it changes shape—moving from "What will happen?" to "How will they deal with what happened?" This phase often includes a moment of "final suspense" where the protagonist might have one last chance to succeed or fail before the end.
                MARKDOWN
            ],
            [
                'order_index' => 5,
                'title' => 'Catastrophe',
                'goal' => <<<'MARKDOWN'
                This is the "wreckage" at the end of a tragedy. Derived from the Greek word for "overturning," it is the final, disastrous conclusion where the protagonist meets their end—whether through death, total social ruin, or spiritual destruction. For the writer, the Catastrophe is the final proof of the hero’s "Tragic Flaw." It isn't just a sad ending; it is the logical and inevitable result of every choice the hero made during the Ascent.
                MARKDOWN
            ],
        ]);

        // =========================================================
        // 4. Five Commandment Structure (System Template)
        // =========================================================

        $fiveCommandments = Template::create([
            'user_id' => null, 
            'name' => 'The Five Commandments',
            'description' => <<<'MARKDOWN'
            The Five Commandments of Storytelling is a fundamental framework developed by Shawn Coyne as part of the Story Grid methodology. These five elements are the "DNA" of a story; they must exist in every scene, every sequence, every act, and the global story itself to ensure the narrative is functional and engaging.

            Think of these commandments as the mechanical gears of a clock. If one gear is missing, the story stops ticking. They ensure that a character is constantly forced to make meaningful choices that reveal their true nature.
            MARKDOWN,
            'image_preview' => 'structures.five-commandments',
            'is_custom' => false,
        ]);

        $fiveCommandments->sections()->createMany([
            [
                'order_index' => 1,
                'title' => 'Inciting Incident',
                'goal' => <<<'MARKDOWN'
                This is the event that upsets the initial balance of the protagonist's life. It can be a Causal event (someone makes a decision that affects the hero) or a Coincidental event (a random act of fate). This incident knocks the character off-balance and gives them a "Goal" to return things to normal or to reach a new state of stability. For the writer, this is the "hook" that demands the character take action.
                MARKDOWN
            ],
            [
                'order_index' => 2,
                'title' => 'Turning Point Complication',
                'goal' => <<<'MARKDOWN'
                As the character pursues their goal, they encounter obstacles. The most critical obstacle is the Turning Point. This is a complication that makes the character's initial plan impossible to continue. It can be an Action (something happens to them) or a Revelation (they learn something new). It effectively "turns" the story in a new direction, leaving the character at a dead end where their old way of thinking no longer works.
                MARKDOWN
            ],
            [
                'order_index' => 3,
                'title' => 'Crisis',
                'goal' => <<<'MARKDOWN'
                This is the most important commandment. Once the Turning Point occurs, the character is forced to make a choice. A true Crisis is not a choice between "Good" and "Bad" (which is easy); it is a choice between two Irreconcilable Goods (you can only have one) or the Best Bad Choice (two terrible options, and you must pick the least harmful). This is the "Dark Night of the Soul" in miniature, where the character’s internal values are put to the ultimate test.
                MARKDOWN
            ],
            [
                'order_index' => 4,
                'title' => 'Climax',
                'goal' => <<<'MARKDOWN'
                This is the character's answer to the Crisis. It is the physical or verbal action they take based on their decision. The Climax is where the character's "mask" is removed—we see who they truly are by what they do when the pressure is highest. A writer must ensure the Climax is a direct result of the Crisis; it is the moment the character finally commits to a path.
                MARKDOWN
            ],
            [
                'order_index' => 5,
                'title' => 'Resolution',
                'goal' => <<<'MARKDOWN'
                This is the aftermath of the Climax. It shows the audience exactly what has changed as a result of the character's choice. The world has shifted to a new state of equilibrium. It provides the "takeaway" for the scene or story, showing the change in the character's circumstances or their internal growth. Without a resolution, the audience feels the story was cut off before they could process the meaning of the Climax.
                MARKDOWN
            ],
        ]);

        // =========================================================
        // 5. Save The Cat Structure (System Template)
        // =========================================================

        $saveTheCat = Template::create([
            'user_id' => null, 
            'name' => 'Save The Cat!',
            'description' => <<<'MARKDOWN'
            Save the Cat! is a highly influential beat sheet created by screenwriter Blake Snyder. While originally intended for film, it has become a go-to framework for novelists and storytellers of all kinds. It focuses on pacing and emotional resonance, breaking a story down into 15 essential "beats" that guide the reader through a satisfying transformation.

            Think of this structure as a roadmap for the audience’s emotions. It ensures that the story doesn't just move forward, but that it hits specific psychological milestones—like the need for a theme, a moment of doubt, and a final triumph—that make a story feel complete.
            MARKDOWN,
            'image_preview' => 'structures.save-the-cat',
            'is_custom' => false,
        ]);

        $saveTheCat->sections()->createMany([
            [
                'order_index' => 1,
                'title' => 'Opening Image',
                'goal' => <<<'MARKDOWN'
                This is a visual or narrative snapshot of the hero’s life before the story starts. It sets the tone, mood, and style of the journey ahead. For the writer, this is the "Before" photo—it should clearly represent the status quo that is about to be disrupted.
                MARKDOWN
            ],
            [
                'order_index' => 2,
                'title' => 'Theme Stated',
                'goal' => <<<'MARKDOWN'
                Usually a passing comment made to the hero that they don’t quite understand yet. It is a statement of the story’s "truth" or the lesson the hero needs to learn by the end. It plants a seed in the reader's mind about what the story is really about (e.g., "Family is more important than money").
                MARKDOWN
            ],
            [
                'order_index' => 3,
                'title' => 'Set-up',
                'goal' => <<<'MARKDOWN'
                This is the detailed exploration of the hero’s life. We see their flaws, their "stasis = death" situation, and the people they interact with. A writer uses this section to show the hero’s "missing piece"—the internal void that makes them vulnerable and human.
                MARKDOWN
            ],
            [
                'order_index' => 4,
                'title' => 'Catalyst',
                'goal' => <<<'MARKDOWN'
                The life-changing event that telegrams the start of the adventure. It is the telegram, the knock on the door, or the sudden firing that forces the hero out of their comfort zone. The world as they know it is gone, and they cannot ignore the change.
                MARKDOWN
            ],
            [
                'order_index' => 5,
                'title' => 'Debate',
                'goal' => <<<'MARKDOWN'
                The hero’s moment of doubt. They ask, "Should I go?" or "Can I really do this?" This section is crucial because it makes the hero relatable. By showing their fear or hesitation, the writer validates the danger of the journey and makes the hero's eventual choice to proceed more heroic.
                MARKDOWN
            ],
            [
                'order_index' => 6,
                'title' => 'Break into Two',
                'goal' => <<<'MARKDOWN'
                The hero makes a proactive choice to leave the old world and enter the new one. This is the bridge between Act 1 and Act 2. The hero isn't being pushed anymore; they are stepping into the unknown with a clear goal in mind.
                MARKDOWN
            ],
            [
                'order_index' => 7,
                'title' => 'B Story',
                'goal' => <<<'MARKDOWN'
                This is where the "secondary" plot begins, often involving a relationship—be it a romance, a friendship, or a mentorship. The B Story is where the Theme is explored. While the A Story is about the external goal, the B Story is about the internal change.
                MARKDOWN
            ],
            [
                'order_index' => 8,
                'title' => 'Fun and Games',
                'goal' => <<<'MARKDOWN'
                This is the "Promise of the Premise." It’s why the reader bought the book or the ticket. If you’re writing a detective story, this is the investigation. If it’s a superhero story, this is the hero testing their powers. It provides the core entertainment value of the genre.
                MARKDOWN
            ],
            [
                'order_index' => 9,
                'title' => 'Midpoint',
                'goal' => <<<'MARKDOWN'
                The stakes are raised, and the story shifts. It is either a "False Victory" (the hero thinks they’ve won) or a "False Defeat" (the hero thinks they’ve lost). From this point on, the "A" and "B" stories begin to collide, and the clock starts ticking faster.
                MARKDOWN
            ],
            [
                'order_index' => 10,
                'title' => 'Bad Guys Close In',
                'goal' => <<<'MARKDOWN'
                The internal and external forces opposing the hero tighten their grip. The hero’s flaws begin to sabotage their progress, and the obstacles become more personal and dangerous. The pressure mounts until it becomes unbearable.
                MARKDOWN
            ],
            [
                'order_index' => 11,
                'title' => 'All is Lost',
                'goal' => <<<'MARKDOWN'
                The hero suffers a major defeat. This beat often includes a "Whiff of Death"—someone dies, or a part of the hero’s identity dies. It feels like there is no way out, and the original goal from Step 2 now seems impossible.
                MARKDOWN
            ],
            [
                'order_index' => 12,
                'title' => 'Dark Night of the Soul',
                'goal' => <<<'MARKDOWN'
                The hero wallows in their defeat. They reflect on their journey and finally realize the truth of the Theme Stated at the beginning. They admit they were wrong and discover what they actually need to do to fix themselves and their world.
                MARKDOWN
            ],
            [
                'order_index' => 13,
                'title' => 'Break into Three',
                'goal' => <<<'MARKDOWN'
                Armed with a new realization (the "Aha!" moment), the hero finds a solution. They combine the lessons from the B Story with the skills from the A Story to form a new plan for the finale.
                MARKDOWN
            ],
            [
                'order_index' => 14,
                'title' => 'Finale',
                'goal' => <<<'MARKDOWN'
                The hero executes their plan. They confront the antagonist and prove they have changed. The flaws they had in the beginning are gone, replaced by the strength they found during the journey. This is the final showdown where the internal and external conflicts are resolved simultaneously.
                MARKDOWN
            ],
            [
                'order_index' => 15,
                'title' => 'Final Image',
                'goal' => <<<'MARKDOWN'
                The mirror of the Opening Image. This is the "After" photo. It shows the hero in their new world, proving through their environment or demeanor that they are a transformed person.
                MARKDOWN
            ],
        ]);

        // =========================================================
        // 6. 7 Point Story Structure (System Template)
        // =========================================================

        $sevenPointStory = Template::create([
            'user_id' => null, 
            'name' => 'Seven Point Story',
            'description' => <<<'MARKDOWN'
            The 7-Point Story Structure is a highly versatile framework popularized by author Dan Wells. It is often described as a "backward" planning method because it encourages writers to define the ending first and then build the starting point as its perfect opposite. This ensures that every beat in the story is laser-focused on moving the protagonist toward their final transformation.
            Think of this structure as a series of waypoints on a map. It doesn't dictate every single scene, but it provides the critical structural "anchors" that keep the narrative on track, ensuring that the tension rises and the character evolves in a way that feels earned and inevitable.
            MARKDOWN,
            'image_preview' => 'structures.seven-point',
            'is_custom' => false,
        ]);

        $sevenPointStory->sections()->createMany([
            [
                'order_index' => 1,
                'title' => 'Hook',
                'goal' => <<<'MARKDOWN'
                This is the starting point of your story, representing the protagonist's "ordinary world." To make this effective, the Hook should be the thematic opposite of the Resolution. If your character ends the story as a brave warrior, they should start here as a coward. For the writer, the Hook is about establishing a state of dissatisfaction or incompleteness that makes the audience want to see the character change.
                MARKDOWN
            ],
            [
                'order_index' => 2,
                'title' => 'Plot Point 1',
                'goal' => <<<'MARKDOWN'
                This is the "Call to Adventure." An event occurs that pulls the protagonist out of their Hook and into the main conflict. It introduces the primary plot and the stakes involved. This is the moment where the character's world shifts, and they are forced to engage with the story. It is the transition from "business as usual" to "everything has changed."
                MARKDOWN
            ],
            [
                'order_index' => 3,
                'title' => 'Pinch Point 1',
                'goal' => <<<'MARKDOWN'
                This is the first time the audience (and often the hero) sees the true power of the antagonist or the primary conflict. It puts pressure on the protagonist, reminding them that the journey ahead is dangerous. For the writer, this beat is used to raise the stakes and show that the hero’s initial efforts are not enough. It’s a "reality check" that forces the character to take the threat seriously.
                MARKDOWN
            ],
            [
                'order_index' => 4,
                'title' => 'Midpoint',
                'goal' => <<<'MARKDOWN'
                This is the most critical pivot in the structure. The protagonist stops reacting to the world around them and starts acting with purpose. They move from being a victim of circumstance to a proactive driver of the plot. This is usually a moment of discovery or a shift in perspective where the hero decides to stop running and start fighting back.
                MARKDOWN
            ],
            [
                'order_index' => 5,
                'title' => 'Pinch Point 2',
                'goal' => <<<'MARKDOWN'
                The "All is Lost" moment. The antagonist strikes back with even greater force, and the protagonist suffers their greatest defeat yet. It is the ultimate pressure point where it seems impossible for the hero to succeed. For the writer, this beat is designed to strip away the hero’s remaining crutches, forcing them to find the strength within themselves to carry on.
                MARKDOWN
            ],
            [
                'order_index' => 6,
                'title' => 'Plot Turn 2',
                'goal' => <<<'MARKDOWN'
                Just when things are at their darkest, the hero finds the final piece of the puzzle. They discover a hidden strength, a secret piece of information, or a new ally that gives them the tools to win. This beat provides the "spark" that leads directly into the final confrontation. It is the moment where the hero finally understands what they must do to achieve their goal.
                MARKDOWN
            ],
            [
                'order_index' => 7,
                'title' => 'Resolution',
                'goal' => <<<'MARKDOWN'
                This is the final outcome of the story. The conflict is resolved, and the protagonist achieves (or fails to achieve) their goal. Most importantly, the Resolution showcases the character’s completed transformation. Because it is the opposite of the Hook, the audience can clearly see how far the hero has come. The world has reached a new state of equilibrium, and the story’s theme is fully realized.
                MARKDOWN
            ],
        ]);

    
        // =========================================================
        // 7. 27 Chapter Method Structure (System Template)
        // =========================================================

        $ChapterMethod27 = Template::create([
            'user_id' => null, 
            'name' => '27 Chapter Method',
            'description' => <<<'MARKDOWN'
            The 27 Chapter Method is a hyper-structured evolution of the classic Three-Act Structure, breaking the narrative into nine blocks of three chapters each. Popularized by writers like Kat O'Keeffe, this method provides a literal roadmap for a first draft, ensuring that every 10% of the book has a specific purpose and thematic movement.

            Think of it as a meticulous blueprint. Where other structures give you the general shape of the house, this method tells you exactly where every brick and beam should go, preventing the dreaded "middle slump" and ensuring a fast-paced, high-stakes journey from start to finish.
            MARKDOWN,
            'image_preview' => 'structures.chapter-method-27',
            'is_custom' => false,
        ]);

        $ChapterMethod27->sections()->createMany([
            [
                'order_index' => 1,
                'title' => 'Intro',
                'goal' => <<<'MARKDOWN'
                We meet the protagonist in their "Ordinary World." The writer must establish the status quo—how the hero lives and who they think they are—while planting a subtle hint of an internal flaw or a "missing piece" that the journey will eventually address.
                MARKDOWN
            ],
            [
                'order_index' => 2,
                'title' => 'Inciting Incident',
                'goal' => <<<'MARKDOWN'
                A specific event disrupts the hero’s world and presents a problem or opportunity. This is the moment where the story's motor starts running, and the protagonist is forced to recognize that their life is about to change.
                MARKDOWN
            ],
            [
                'order_index' => 3,
                'title' => 'Fallout',
                'goal' => <<<'MARKDOWN'
                This chapter explores the immediate emotional and physical repercussions of the incident. The hero grapples with what has happened, realizing that their world is no longer as safe or predictable as it was in Chapter 1.
                MARKDOWN
            ],
            [
                'order_index' => 4,
                'title' => 'Reaction / Rebellion',
                'goal' => <<<'MARKDOWN'
                The hero tries to maintain their old life or resist the call to change. They might attempt to fix the problem using old, failing habits or simply try to ignore it, showcasing the human tendency to stay in the zone of comfort.
                MARKDOWN
            ],
            [
                'order_index' => 5,
                'title' => 'Action',
                'goal' => <<<'MARKDOWN'
                The hero realizes they cannot simply ignore the problem and takes their first proactive step toward solving it. This is often an experimental move where the character tests the waters of the new conflict.
                MARKDOWN
            ],
            [
                'order_index' => 6,
                'title' => 'Consequence',
                'goal' => <<<'MARKDOWN'
                The hero's first action leads to an unexpected result, often making the situation more complicated. This teaches both the character and the reader that the old ways won't work and that the stakes are higher than initially thought.
                MARKDOWN
            ],
            [
                'order_index' => 7,
                'title' => 'Pressure',
                'goal' => <<<'MARKDOWN'
                External forces—whether the antagonist, a ticking clock, or social pressure—begin to squeeze the hero. The character feels trapped and realizes that staying in the ordinary world is no longer a viable option.
                MARKDOWN
            ],
            [
                'order_index' => 8,
                'title' => 'Pinch',
                'goal' => <<<'MARKDOWN'
                A major obstacle or a direct attack from the antagonist that serves as a "reality check." This chapter highlights the true power of the opposing force and reminds the hero of the catastrophic cost of failure.
                MARKDOWN
            ],
            [
                'order_index' => 9,
                'title' => 'Push',
                'goal' => <<<'MARKDOWN'
                The final momentum that launches the hero out of Act I. The hero makes a definitive choice to leave their old life behind and fully commit to the journey, crossing the threshold into the unknown.
                MARKDOWN
            ],
            [
                'order_index' => 10,
                'title' => 'New World',
                'goal' => <<<'MARKDOWN'
                The hero enters the core setting of the story—the "upside down," the new city, or the magical realm. This chapter focuses on world-building and showing how the rules here differ fundamentally from the hero's home.
                MARKDOWN
            ],
            [
                'order_index' => 11,
                'title' => 'Fun & Games',
                'goal' => <<<'MARKDOWN'
                This is the "promise of the premise." Here, the writer delivers the specific type of action the reader expected: the investigation, the training montage, or the burgeoning romance that defines the genre.
                MARKDOWN
            ],
            [
                'order_index' => 12,
                'title' => 'Juxtaposition',
                'goal' => <<<'MARKDOWN'
                A moment of reflection where the hero compares their old life to their new one. It highlights their growth (or lack thereof) and emphasizes the themes of the story by showing how much has changed in a short time.
                MARKDOWN
            ],
            [
                'order_index' => 13,
                'title' => 'Buildup',
                'goal' => <<<'MARKDOWN'
                Tensions that were simmering begin to boil. Subplots tighten, allies start to have disagreements, and the hero begins to feel that their current progress is about to be challenged by something much larger.
                MARKDOWN
            ],
            [
                'order_index' => 14,
                'title' => 'Midpoint',
                'goal' => <<<'MARKDOWN'
                A massive pivot. The hero moves from being "reactive" to "proactive." They uncover a secret or have a breakthrough that changes their understanding of the goal, deciding to take the fight to the enemy.
                MARKDOWN
            ],
            [
                'order_index' => 15,
                'title' => 'Reversal',
                'goal' => <<<'MARKDOWN'
                Immediately following the midpoint, the hero’s new plan goes wrong. A character might betray them, or a hidden trap is sprung. This forces the hero to realize that the "easy win" they expected is impossible.
                MARKDOWN
            ],
            [
                'order_index' => 16,
                'title' => 'Consequence',
                'goal' => <<<'MARKDOWN'
                The fallout of the reversal. The hero must deal with the damage caused by their failed plan. This is a moment of regrouping where the hero assesses their losses and prepares for a much darker path.
                MARKDOWN
            ],
            [
                'order_index' => 17,
                'title' => 'Trials',
                'goal' => <<<'MARKDOWN'
                The hero undergoes a series of grueling tests that push them to their physical or emotional limits. This is where they learn the final skills or gather the internal strength required for the ultimate confrontation.
                MARKDOWN
            ],
            [
                'order_index' => 18,
                'title' => 'Dedication',
                'goal' => <<<'MARKDOWN'
                The hero recommits to the mission with no illusions. They accept the possibility of total failure and decide to push forward regardless, setting the stage for the final act.
                MARKDOWN
            ],
            [
                'order_index' => 19,
                'title' => 'Calm Before the Storm',
                'goal' => <<<'MARKDOWN'
                A brief moment of peace or planning before the final battle. It allows characters to have a final emotional connection or a moment of reflection, making the upcoming stakes feel deeply personal.
                MARKDOWN
            ],
            [
                'order_index' => 20,
                'title' => 'Plot Twist',
                'goal' => <<<'MARKDOWN'
                A final, unexpected revelation that changes the hero’s plan at the last minute. This forces the protagonist to adapt one final time, proving they have truly learned to navigate the chaos of the story.
                MARKDOWN
            ],
            [
                'order_index' => 21,
                'title' => 'Darkest Point',
                'goal' => <<<'MARKDOWN'
                The "All is Lost" moment. The hero is at their most vulnerable, and success feels further away than ever. It is the moment where the hero’s original flaw (from Chapter 1) is most dangerous to them.
                MARKDOWN
            ],
            [
                'order_index' => 22,
                'title' => 'Power Within',
                'goal' => <<<'MARKDOWN'
                The hero has an epiphany. They finally overcome their internal flaw, which gives them the clarity or spiritual power needed to take a final stand against the antagonist.
                MARKDOWN
            ],
            [
                'order_index' => 23,
                'title' => 'Action & Games',
                'goal' => <<<'MARKDOWN'
                The hero launches their final attack. They are no longer doubting themselves or their mission; they are moving with absolute purpose and conviction, utilizing everything they have learned.
                MARKDOWN
            ],
            [
                'order_index' => 24,
                'title' => 'Convergence',
                'goal' => <<<'MARKDOWN'
                All subplots and side characters converge. Every thread the writer has woven—romantic interests, side-kicks, and secondary villains—starts to tie together into a single, high-stakes moment.
                MARKDOWN
            ],
            [
                'order_index' => 25,
                'title' => 'The Final Battle',
                'goal' => <<<'MARKDOWN'
                The peak of the external conflict. This is the direct confrontation between the hero and the antagonist. It is the most intense action sequence or emotional debate in the entire narrative.
                MARKDOWN
            ],
            [
                'order_index' => 26,
                'title' => 'Climax',
                'goal' => <<<'MARKDOWN'
                The ultimate resolution of the hero’s internal arc. While the "Final Battle" deals with the outside world, the Climax deals with the soul. The hero proves they are changed and makes the final sacrifice or decision.
                MARKDOWN
            ],
            [
                'order_index' => 27,
                'title' => 'Resolution',
                'goal' => <<<'MARKDOWN'
                The aftermath. We see the "new ordinary world" and how the hero fits into it. All remaining questions are answered, and the audience is given a sense of closure and thematic satisfaction.
                MARKDOWN
            ],
        ]);
    }
}